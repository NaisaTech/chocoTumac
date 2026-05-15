<?php
/**
 * Trait Redirectable – ChocoTumac
 *
 * Centraliza toda la lógica de redirección HTTP en un único lugar,
 * eliminando la duplicación del literal "Location: " en todos los
 * controladores (Code Smell detectado por SonarCloud).
 *
 * Principios aplicados:
 *   - DRY  : una sola definición del encabezado Location.
 *   - SRP  : el trait sólo se ocupa de redirigir.
 *   - KISS : métodos cortos y con nombres descriptivos.
 *
 * @package ChocoTumac
 */
trait Redirectable
{
    /**
     * Redirige a una URL relativa a BASE_URL y termina la ejecución.
     *
     * @param string      $path   Ruta relativa a BASE_URL (ej. "index.php?view=clientes").
     * @param string|null $msg    Clave de mensaje de éxito (parámetro &msg=).
     * @param string|null $error  Mensaje de error a codificar  (parámetro &error=).
     * @return never
     */
    private function redirect(string $path, ?string $msg = null, ?string $error = null): never
    {
        $url = BASE_URL . $path;

        if ($msg !== null) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'msg=' . urlencode($msg);
        }

        if ($error !== null) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'error=' . urlencode($error);
        }

        header('Location: ' . $url);
        exit();
    }

    /**
     * Redirige con un parámetro de error preformateado en la URL de destino.
     * Atajo para el caso más común: redirigir a una vista con mensaje de error.
     *
     * @param string $view  Nombre de la vista destino (ej. "clientes").
     * @param string $error Mensaje de error legible para el usuario.
     * @return never
     */
    private function redirectError(string $view, string $error): never
    {
        $this->redirect("index.php?view={$view}", null, $error);
    }

    /**
     * Redirige con un parámetro de éxito en la URL de destino.
     *
     * @param string $view Vista destino (ej. "clientes").
     * @param string $msg  Clave de mensaje de éxito (ej. "creado", "actualizado").
     * @return never
     */
    private function redirectOk(string $view, string $msg): never
    {
        $this->redirect("index.php?view={$view}", $msg);
    }
}