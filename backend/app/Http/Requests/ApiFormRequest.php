<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest del API: mensajes y nombres de campo en espanol
 * compartidos (APP_LOCALE es "en", no hay carpeta lang/). El permiso por
 * recurso ya lo aplica el middleware `can:` en las rutas, por eso authorize
 * devuelve true aqui.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'email' => 'El correo no tiene un formato valido.',
            'regex' => 'El campo :attribute tiene un formato no valido.',
            'exists' => 'El :attribute seleccionado no existe.',
            'in' => 'El valor de :attribute no es una opcion valida.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'date_format' => 'El campo :attribute debe tener el formato :format.',
            'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.',
            'before_or_equal' => 'El campo :attribute no puede ser una fecha futura.',
            'numeric' => 'El campo :attribute debe ser un numero.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'min' => 'El campo :attribute no cumple el minimo permitido.',
            'max' => 'El campo :attribute supera el maximo permitido.',
            'uuid' => 'El identificador enviado no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'codigo',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'identification_type' => 'tipo de documento',
            'identification_number' => 'numero de documento',
            'email' => 'correo',
            'hire_date' => 'fecha de ingreso',
            'employment_status' => 'estado',
            'department_id' => 'area',
            'position_id' => 'cargo',
            'manager_id' => 'jefe',
            'salary' => 'salario',
            'employee_id' => 'empleado',
            'name' => 'nombre',
            'description' => 'descripcion',
            'status' => 'estado',
            'date' => 'fecha',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'issue_date' => 'fecha de emision',
            'expiration_date' => 'fecha de vencimiento',
            'requested_days' => 'dias solicitados',
            'days' => 'dias',
            'type' => 'tipo',
            'reason' => 'motivo',
            'rejection_reason' => 'motivo de rechazo',
            'document_type' => 'tipo de documento',
            'file_path' => 'archivo',
            'check_in' => 'hora de entrada',
            'check_out' => 'hora de salida',
            'late_minutes' => 'minutos de retraso',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'break_minutes' => 'minutos de descanso',
            'notes' => 'notas',
        ];
    }
}
