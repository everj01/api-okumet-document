<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\Message;
use App\Exceptions\IaException;
use App\Models\Documento;
use App\Models\DocumentoPagina;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AsistenteIa
{
    // Largo máximo que acepta el formulario de expediente del frontend.
    private const LIMITES = [
        'codigo' => 60,
        'titulo' => 180,
        'materia' => 100,
        'juzgado' => 150,
        'fecha_inicio' => 10,
    ];

    // Reglas comunes a las tres tareas: nada aquí puede variar entre llamadas (fechas, usuarios, ids) o invalida el caché en silencio.
    private const CIERRE_SISTEMA = <<<'TXT'
    Reglas que aplican siempre:
    - Responde en español, en el registro jurídico que usaría un abogado peruano.
    - Usa únicamente el contenido del documento. Si algo no aparece, dilo; no lo infieras ni lo completes con conocimiento general.
    - Cada página del documento viene precedida por un marcador [Página N]. Cuando cites páginas, usa solo números que aparezcan en esos marcadores.
    - Nunca inventes una cita: en materia legal una página equivocada es peor que no citar ninguna.
    TXT;

    private ?Client $cliente = null;

    public function __construct(private readonly LimiteClaudeService $limite) {}

    public function disponible(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function resumir(Documento $documento): string
    {
        $instrucciones = <<<'TXT'
        Tarea: redactar el resumen del documento para un abogado que todavía no lo ha abierto.

        Cubre, en este orden y solo si aparece en el documento:
        - qué tipo de documento es;
        - quiénes son las partes y en qué calidad intervienen;
        - qué se pide o qué se resuelve;
        - qué fechas, plazos o vencimientos aparecen.

        Formato: texto plano, sin Markdown. No uses asteriscos, almohadillas, viñetas ni
        numeración: el resultado se muestra tal cual y los símbolos se verían literales.
        Separa las ideas con saltos de línea. Máximo 250 palabras. Sin relleno y sin
        repetir el nombre del archivo.
        TXT;

        $mensaje = $this->invocar($documento, $instrucciones, 'Redacta el resumen.', 'medium');
        $resumen = trim($this->textoDe($mensaje));

        if ($resumen === '') {
            throw IaException::respuestaInesperada();
        }

        return $resumen;
    }

    /**
     * @return array{respuesta: string, paginas: list<int>}
     */
    public function preguntar(Documento $documento, string $pregunta): array
    {
        $instrucciones = <<<'TXT'
        Tarea: responder la pregunta del abogado sobre este documento.

        En "respuesta" escribe la respuesta en texto plano, sin Markdown, lo más concreta
        posible. Si el documento no contiene la respuesta, dilo explícitamente en lugar de
        deducirla, y deja "paginas" vacío.

        En "paginas" pon los números de las páginas en las que efectivamente leíste lo que
        estás afirmando, tomados de los marcadores [Página N]. Si no estás seguro de una
        página, no la incluyas.
        TXT;

        $esquema = [
            'type' => 'object',
            'properties' => [
                'respuesta' => ['type' => 'string'],
                'paginas' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'required' => ['respuesta', 'paginas'],
            'additionalProperties' => false,
        ];

        $datos = $this->invocarJson($documento, $instrucciones, $pregunta, 'medium', $esquema);
        $respuesta = trim((string) ($datos['respuesta'] ?? ''));

        if ($respuesta === '') {
            throw IaException::respuestaInesperada();
        }

        return [
            'respuesta' => $respuesta,
            'paginas' => $this->paginasValidas($datos['paginas'] ?? [], $documento),
        ];
    }

    /**
     * @return array{campos: array<string, array{valor: string, confianza: string, pagina: int|null}>, partes: list<array{nombre: string, rol: string, pagina: int|null}>, fechas_clave: list<array{fecha: string, descripcion: string, pagina: int|null}>}
     */
    public function extraer(Documento $documento): array
    {
        $instrucciones = <<<'TXT'
        Tarea: detectar los datos del expediente que este documento permite leer, para que
        un abogado los revise y decida si los aplica. No estás rellenando un formulario:
        estás informando qué dice el documento.

        En "campos" incluye únicamente los campos que realmente encontraste. Omite por
        completo los que no aparezcan; no devuelvas un campo con valor vacío ni inventado.
        Significado de cada campo:
        - codigo: el número o código de expediente tal como figura en el documento.
        - titulo: una denominación corta de la causa (por ejemplo "Demanda de alimentos").
        - materia: la rama del derecho (Familia, Civil, Penal, Laboral, etc.).
        - juzgado: el órgano jurisdiccional completo, como esté escrito.
        - fecha_inicio: la fecha de inicio o de presentación del expediente.

        "confianza" debe ser honesta y es lo que decide si el abogado lo revisa a mano:
        - "alta": lo leíste literalmente en el documento;
        - "media": se desprende del documento con claridad pero no está escrito así;
        - "baja": lo estás infiriendo.
        Si estás infiriendo, pon "baja". Preferimos un campo marcado como bajo a un campo
        presentado como seguro.

        Toda fecha ("fecha_inicio" y las de "fechas_clave") va en formato YYYY-MM-DD, con
        año de cuatro cifras. Si no puedes determinar el día o el mes exactos, no devuelvas
        la fecha: omítela.

        Largos máximos, porque el formulario de destino los rechaza: codigo 60 caracteres,
        titulo 180, materia 100, juzgado 150. Si lo que leíste no cabe, acórtalo tú y baja
        la confianza a "baja".

        En "partes" lista las personas o entidades que intervienen, con su rol en minúsculas
        (demandante, demandado, tercero, procurador, etc.).
        En "fechas_clave" lista audiencias, plazos y vencimientos con una descripción corta.
        Ambas listas pueden ir vacías. "pagina" es null cuando no puedas ubicar el dato.
        TXT;

        $campo = [
            'type' => 'object',
            'properties' => [
                'campo' => ['type' => 'string', 'enum' => array_keys(self::LIMITES)],
                'valor' => ['type' => 'string'],
                'confianza' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']],
                'pagina' => ['type' => ['integer', 'null']],
            ],
            'required' => ['campo', 'valor', 'confianza', 'pagina'],
            'additionalProperties' => false,
        ];

        $esquema = [
            'type' => 'object',
            'properties' => [
                // Lista en vez de objeto: "no encontrado" es simplemente no estar, sin null para los ausentes.
                'campos' => ['type' => 'array', 'items' => $campo],
                'partes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nombre' => ['type' => 'string'],
                            'rol' => ['type' => 'string'],
                            'pagina' => ['type' => ['integer', 'null']],
                        ],
                        'required' => ['nombre', 'rol', 'pagina'],
                        'additionalProperties' => false,
                    ],
                ],
                'fechas_clave' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'fecha' => ['type' => 'string'],
                            'descripcion' => ['type' => 'string'],
                            'pagina' => ['type' => ['integer', 'null']],
                        ],
                        'required' => ['fecha', 'descripcion', 'pagina'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['campos', 'partes', 'fechas_clave'],
            'additionalProperties' => false,
        ];

        $datos = $this->invocarJson(
            $documento,
            $instrucciones,
            'Extrae los datos del expediente que permita leer este documento.',
            'high',
            $esquema
        );

        return [
            'campos' => $this->camposValidos($datos['campos'] ?? [], $documento),
            'partes' => $this->partesValidas($datos['partes'] ?? [], $documento),
            'fechas_clave' => $this->fechasValidas($datos['fechas_clave'] ?? [], $documento),
        ];
    }

    /* El texto del documento viaja como primer bloque de `system` y es el único cacheado, así los tres usos
    (resumen, preguntas, extracción) comparten el mismo prefijo y solo la primera llamada paga el texto completo.
    Las instrucciones de cada tarea van después, nunca antes. */
    private function invocar(
        Documento $documento,
        string $instrucciones,
        string $pregunta,
        string $effort,
        ?array $esquema = null,
    ): Message {
        $sistema = [
            [
                'type' => 'text',
                'text' => $this->textoDelDocumento($documento),
                'cacheControl' => ['type' => 'ephemeral', 'ttl' => '1h'],
            ],
            ['type' => 'text', 'text' => self::CIERRE_SISTEMA],
            ['type' => 'text', 'text' => $instrucciones],
        ];

        $salida = [];

        if ($esquema !== null) {
            $salida['format'] = ['type' => 'json_schema', 'schema' => $esquema];
        }

        // Se cuenta y bloquea aquí, antes de pagar la llamada real: cualquier tarea que pase por
        // este método (presente o futura) queda cubierta por el límite mensual sin tocar nada más.
        $usuario = Auth::user();

        if ($usuario instanceof User) {
            $this->limite->verificarYRegistrar($usuario);
        }

        try {
            $mensaje = $this->cliente()->messages->create(
                model: config('services.anthropic.model'),
                maxTokens: 16000,
                outputConfig: $salida,
                system: $sistema,
                // Lo único variable va en messages, después del prefijo cacheado.
                messages: [['role' => 'user', 'content' => $pregunta]],
            );
        } catch (APIStatusException $e) {
            Log::error('claude.error', [
                'documento_id' => $documento->id,
                'tipo' => $e->type?->value,
                'mensaje' => $e->getMessage(),
            ]);

            throw IaException::servicio($e->type?->value ?? 'error de la API');
        } catch (Throwable $e) {
            Log::error('claude.error', ['documento_id' => $documento->id, 'mensaje' => $e->getMessage()]);

            throw IaException::servicio('no se pudo contactar el servicio');
        }

        Log::info('claude.cache', [
            'documento_id' => $documento->id,
            'effort' => $effort,
            'entrada' => $mensaje->usage->inputTokens,
            'creados' => $mensaje->usage->cacheCreationInputTokens,
            // Debe ser > 0 desde la segunda llamada sobre el mismo documento.
            'leidos' => $mensaje->usage->cacheReadInputTokens,
            'salida' => $mensaje->usage->outputTokens,
        ]);

        if ($mensaje->stopReason === 'refusal') {
            throw IaException::servicio('la solicitud fue rechazada por las políticas del modelo');
        }

        return $mensaje;
    }

    private function invocarJson(
        Documento $documento,
        string $instrucciones,
        string $pregunta,
        string $effort,
        array $esquema,
    ): array {
        $mensaje = $this->invocar($documento, $instrucciones, $pregunta, $effort, $esquema);
        $datos = json_decode($this->textoDe($mensaje), true);

        if (! is_array($datos)) {
            Log::warning('claude.json_invalido', ['documento_id' => $documento->id]);

            throw IaException::respuestaInesperada();
        }

        return $datos;
    }

    private function cliente(): Client
    {
        if (! $this->disponible()) {
            throw IaException::sinConfigurar();
        }

        return $this->cliente ??= new Client(apiKey: config('services.anthropic.key'));
    }

    // Con thinking activo el primer bloque puede ser un ThinkingBlock, por eso se filtra por tipo.
    private function textoDe(Message $mensaje): string
    {
        $partes = [];

        foreach ($mensaje->content as $bloque) {
            if ($bloque->type === 'text') {
                $partes[] = $bloque->text;
            }
        }

        return implode("\n", $partes);
    }

    // Nunca recorta el texto: si no cabe en el límite, falla.
    private function textoDelDocumento(Documento $documento): string
    {
        $texto = $documento->paginasTexto
            ->map(fn (DocumentoPagina $pagina) => "[Página {$pagina->numero}]\n{$pagina->texto}")
            ->implode("\n\n");

        if (trim(preg_replace('/\[Página \d+\]/', '', $texto) ?? '') === '') {
            throw IaException::sinTexto();
        }

        $limite = (int) config('services.anthropic.limite_caracteres');
        $largo = mb_strlen($texto);

        if ($limite > 0 && $largo > $limite) {
            throw IaException::demasiadoLargo($largo, $limite);
        }

        return $texto;
    }

    /**
     * @return list<int>
     */
    private function paginasValidas(mixed $paginas, Documento $documento): array
    {
        if (! is_array($paginas)) {
            return [];
        }

        $validas = [];

        foreach ($paginas as $pagina) {
            $numero = $this->paginaValida($pagina, $documento);

            if ($numero !== null) {
                $validas[$numero] = true;

                continue;
            }

            Log::warning('claude.pagina_inventada', [
                'documento_id' => $documento->id,
                'pagina' => $pagina,
                'total' => $documento->paginas,
            ]);
        }

        $numeros = array_keys($validas);
        sort($numeros);

        return array_values($numeros);
    }

    private function paginaValida(mixed $pagina, Documento $documento): ?int
    {
        if (! is_numeric($pagina)) {
            return null;
        }

        $numero = (int) $pagina;

        return $numero >= 1 && $numero <= $documento->paginas ? $numero : null;
    }

    /**
     * @return array<string, array{valor: string, confianza: string, pagina: int|null}>
     */
    private function camposValidos(mixed $campos, Documento $documento): array
    {
        if (! is_array($campos)) {
            return [];
        }

        $validos = [];

        foreach ($campos as $campo) {
            if (! is_array($campo)) {
                continue;
            }

            $clave = is_string($campo['campo'] ?? null) ? $campo['campo'] : null;

            if ($clave === null || ! array_key_exists($clave, self::LIMITES) || isset($validos[$clave])) {
                continue;
            }

            $valor = trim((string) ($campo['valor'] ?? ''));

            if ($valor === '') {
                continue;
            }

            $confianza = in_array($campo['confianza'] ?? null, ['alta', 'media', 'baja'], true)
                ? $campo['confianza']
                : 'baja';

            if ($clave === 'fecha_inicio') {
                $fecha = $this->fechaIso($valor);

                if ($fecha === null) {
                    continue;
                }

                $valor = $fecha;
            } elseif (mb_strlen($valor) > self::LIMITES[$clave]) {
                // Recortar es preferible a que el formulario lo rechace, pero ya no es un dato leído tal cual: baja la confianza.
                $valor = rtrim(mb_substr($valor, 0, self::LIMITES[$clave]));
                $confianza = 'baja';
            }

            $validos[$clave] = [
                'valor' => $valor,
                'confianza' => $confianza,
                'pagina' => $this->paginaValida($campo['pagina'] ?? null, $documento),
            ];
        }

        return $validos;
    }

    /**
     * @return list<array{nombre: string, rol: string, pagina: int|null}>
     */
    private function partesValidas(mixed $partes, Documento $documento): array
    {
        if (! is_array($partes)) {
            return [];
        }

        $validas = [];

        foreach ($partes as $parte) {
            if (! is_array($parte)) {
                continue;
            }

            $nombre = trim((string) ($parte['nombre'] ?? ''));

            if ($nombre === '') {
                continue;
            }

            $validas[] = [
                'nombre' => mb_substr($nombre, 0, 180),
                'rol' => mb_strtolower(mb_substr(trim((string) ($parte['rol'] ?? '')), 0, 60)),
                'pagina' => $this->paginaValida($parte['pagina'] ?? null, $documento),
            ];
        }

        return $validas;
    }

    /**
     * @return list<array{fecha: string, descripcion: string, pagina: int|null}>
     */
    private function fechasValidas(mixed $fechas, Documento $documento): array
    {
        if (! is_array($fechas)) {
            return [];
        }

        $validas = [];

        foreach ($fechas as $fecha) {
            if (! is_array($fecha)) {
                continue;
            }

            $iso = $this->fechaIso((string) ($fecha['fecha'] ?? ''));

            if ($iso === null) {
                continue;
            }

            $validas[] = [
                'fecha' => $iso,
                'descripcion' => mb_substr(trim((string) ($fecha['descripcion'] ?? '')), 0, 180),
                'pagina' => $this->paginaValida($fecha['pagina'] ?? null, $documento),
            ];
        }

        return $validas;
    }

    // YYYY-MM-DD estricto y existente: el `<input type="date">` del frontend descarta en silencio cualquier otro formato.
    private function fechaIso(string $valor): ?string
    {
        $valor = trim($valor);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) !== 1) {
            return null;
        }

        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : null;
    }
}
