<?php

namespace App\Core;

/**
 * Controlador base.
 * Todos los controladores extienden esta clase.
 */
abstract class Controller
{
    /**
     * Renderizar una vista dentro del layout.
     *
     * @param string $view  Ruta de la vista (ej: 'home/index')
     * @param array  $data  Datos para la vista
     */
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Renderizar sin layout.
     */
    protected function partial(string $view, array $data = []): void
    {
        View::renderPartial($view, $data);
    }

    /**
     * Redirigir a una ruta.
     */
    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }

    /**
     * Redirigir con mensaje flash de éxito.
     */
    protected function redirectSuccess(string $url, string $message): never
    {
        Response::redirectSuccess($url, $message);
    }

    /**
     * Redirigir con mensaje flash de error.
     */
    protected function redirectError(string $url, string $message): never
    {
        Response::redirectError($url, $message);
    }

    /**
     * Obtener modelo instanciado.
     */
    protected function model(string $class): object
    {
        $fqn = "\\App\\Models\\$class";
        return new $fqn();
    }

    /**
     * Validar datos con reglas simples.
     * 
     * @param array $data  Datos a validar
     * @param array $rules Reglas (campo => 'required|min:3|max:100|...')
     * @return array Errores (vacío si válido)
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleStr);

            foreach ($ruleList as $rule) {
                [$ruleName, $param] = explode(':', $rule, 2) + [1 => null];

                match ($ruleName) {
                    'required' => (empty($value) && $value !== '0')
                        ? $errors[$field][] = "El campo es obligatorio."
                        : null,
                    'min' => (strlen((string)$value) < (int)$param)
                        ? $errors[$field][] = "Mínimo $param caracteres."
                        : null,
                    'max' => (strlen((string)$value) > (int)$param)
                        ? $errors[$field][] = "Máximo $param caracteres."
                        : null,
                    'email' => (!filter_var($value, FILTER_VALIDATE_EMAIL))
                        ? $errors[$field][] = "Correo electrónico inválido."
                        : null,
                    'numeric' => (!is_numeric($value))
                        ? $errors[$field][] = "Debe ser un valor numérico."
                        : null,
                    'integer' => (!ctype_digit((string)$value))
                        ? $errors[$field][] = "Debe ser un número entero."
                        : null,
                    'in' => (!in_array($value, explode(',', $param), true))
                        ? $errors[$field][] = "Valor no permitido."
                        : null,
                    'password_strong' => (!preg_match(
                        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                        (string)$value
                    ))
                        ? $errors[$field][] = "La clave debe tener mínimo 8 caracteres, mayúscula, minúscula, número y símbolo."
                        : null,
                    default => null,
                };
            }
        }

        return $errors;
    }

    /**
     * Abortar con código HTTP.
     */
    protected function abort(int $code, string $message = ''): never
    {
        Response::abort($code, $message);
    }

    /**
     * Respuesta JSON.
     */
    protected function json(mixed $data, int $code = 200): never
    {
        Response::json($data, $code);
    }
}
