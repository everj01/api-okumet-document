<?php

namespace Tests\Unit;

use App\Models\Documento;
use App\Services\AsistenteIa;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Lo que Claude devuelve no se publica tal cual: estas son las reglas que
 * impiden que una página inventada o una fecha inválida lleguen a la UI.
 * Son métodos privados porque solo tienen sentido sobre una respuesta del
 * modelo, así que se prueban por reflexión.
 */
class AsistenteIaTest extends TestCase
{
    private function invocar(string $metodo, mixed ...$argumentos): mixed
    {
        $reflexion = new ReflectionMethod(AsistenteIa::class, $metodo);
        $reflexion->setAccessible(true);

        return $reflexion->invoke(app(AsistenteIa::class), ...$argumentos);
    }

    private function documento(int $paginas): Documento
    {
        return new Documento(['paginas' => $paginas]);
    }

    public function test_descarta_las_paginas_fuera_del_documento(): void
    {
        $paginas = $this->invocar('paginasValidas', [1, 3, 9, 0, -2], $this->documento(5));

        $this->assertSame([1, 3], $paginas);
    }

    public function test_devuelve_lista_vacia_y_nunca_null(): void
    {
        $this->assertSame([], $this->invocar('paginasValidas', [], $this->documento(5)));
        $this->assertSame([], $this->invocar('paginasValidas', null, $this->documento(5)));
    }

    public function test_deduplica_y_ordena_las_paginas(): void
    {
        $paginas = $this->invocar('paginasValidas', [7, 2, 2, 7], $this->documento(10));

        $this->assertSame([2, 7], $paginas);
    }

    public function test_recorta_un_campo_largo_y_baja_la_confianza(): void
    {
        $campos = $this->invocar('camposValidos', [
            ['campo' => 'titulo', 'valor' => str_repeat('a', 200), 'confianza' => 'alta', 'pagina' => 1],
        ], $this->documento(3));

        $this->assertSame(180, mb_strlen($campos['titulo']['valor']));
        $this->assertSame('baja', $campos['titulo']['confianza']);
    }

    public function test_omite_los_campos_desconocidos_y_los_vacios(): void
    {
        $campos = $this->invocar('camposValidos', [
            ['campo' => 'estado', 'valor' => 'abierto', 'confianza' => 'alta', 'pagina' => 1],
            ['campo' => 'materia', 'valor' => '   ', 'confianza' => 'alta', 'pagina' => 1],
            ['campo' => 'juzgado', 'valor' => '2º Juzgado de Familia', 'confianza' => 'media', 'pagina' => 1],
        ], $this->documento(3));

        $this->assertSame(['juzgado'], array_keys($campos));
    }

    public function test_anula_la_pagina_inventada_de_un_campo(): void
    {
        $campos = $this->invocar('camposValidos', [
            ['campo' => 'codigo', 'valor' => 'EXP-2026-00231', 'confianza' => 'alta', 'pagina' => 99],
        ], $this->documento(3));

        $this->assertNull($campos['codigo']['pagina']);
        $this->assertSame('EXP-2026-00231', $campos['codigo']['valor']);
    }

    public function test_descarta_fecha_inicio_con_formato_no_iso(): void
    {
        $campos = $this->invocar('camposValidos', [
            ['campo' => 'fecha_inicio', 'valor' => '14/03/2026', 'confianza' => 'alta', 'pagina' => 2],
        ], $this->documento(3));

        $this->assertArrayNotHasKey('fecha_inicio', $campos);
    }

    public function test_acepta_fecha_inicio_iso(): void
    {
        $campos = $this->invocar('camposValidos', [
            ['campo' => 'fecha_inicio', 'valor' => '2026-03-14', 'confianza' => 'media', 'pagina' => 2],
        ], $this->documento(3));

        $this->assertSame('2026-03-14', $campos['fecha_inicio']['valor']);
    }

    public function test_descarta_fechas_clave_inexistentes_en_el_calendario(): void
    {
        $fechas = $this->invocar('fechasValidas', [
            ['fecha' => '2026-02-30', 'descripcion' => 'Audiencia', 'pagina' => 1],
            ['fecha' => '2026-04-02', 'descripcion' => 'Audiencia única', 'pagina' => 5],
        ], $this->documento(6));

        $this->assertCount(1, $fechas);
        $this->assertSame('2026-04-02', $fechas[0]['fecha']);
    }

    public function test_normaliza_el_rol_de_las_partes_a_minusculas(): void
    {
        $partes = $this->invocar('partesValidas', [
            ['nombre' => 'Ana Ríos Paredes', 'rol' => 'DEMANDANTE', 'pagina' => 1],
            ['nombre' => '  ', 'rol' => 'demandado', 'pagina' => 1],
        ], $this->documento(3));

        $this->assertCount(1, $partes);
        $this->assertSame('demandante', $partes[0]['rol']);
    }
}
