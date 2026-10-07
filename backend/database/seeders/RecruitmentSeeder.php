<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobApplication;
use App\Models\JobCandidate;
use App\Models\JobInterview;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        $dept = Department::where('company_id', $company->id)->first();
        $interviewer = User::where('company_id', $company->id)->first();

        // 3 vacantes
        $v1 = JobVacancy::firstOrCreate(
            ['company_id' => $company->id, 'title' => 'Desarrollador Full Stack'],
            ['department_id' => $dept?->id, 'description' => 'Buscamos desarrollador con experiencia en Laravel y React.', 'opened_at' => '2026-09-01', 'status' => 'open', 'vacancies_count' => 2]
        );
        $v2 = JobVacancy::firstOrCreate(
            ['company_id' => $company->id, 'title' => 'Analista Contable'],
            ['department_id' => $dept?->id, 'description' => 'Contador público con experiencia en ERP y nómina.', 'opened_at' => '2026-09-10', 'status' => 'open', 'vacancies_count' => 1]
        );
        $v3 = JobVacancy::firstOrCreate(
            ['company_id' => $company->id, 'title' => 'Asistente de Recursos Humanos'],
            ['department_id' => $dept?->id, 'description' => 'Apoyo en selección y administración de personal.', 'opened_at' => '2026-08-15', 'closed_at' => '2026-09-30', 'status' => 'closed', 'vacancies_count' => 1]
        );

        // 8 candidatos
        $candidates = [];
        $data = [
            ['Andrés', 'Torres', '1098765432', 'andres.torres@email.com', '3001234567'],
            ['Valentina', 'Ríos', '1087654321', 'valentina.rios@email.com', '3002345678'],
            ['Carlos', 'Mendoza', '1076543210', 'carlos.mendoza@email.com', '3003456789'],
            ['Luisa', 'Herrera', '1065432109', 'luisa.herrera@email.com', '3004567890'],
            ['Felipe', 'Castro', '1054321098', 'felipe.castro@email.com', '3005678901'],
            ['Natalia', 'Vargas', '1043210987', 'natalia.vargas@email.com', '3006789012'],
            ['Diego', 'Mora', '1032109876', 'diego.mora@email.com', '3007890123'],
            ['Paola', 'Jiménez', '1021098765', 'paola.jimenez@email.com', '3008901234'],
        ];
        foreach ($data as [$fn, $ln, $id, $email, $phone]) {
            $candidates[] = JobCandidate::firstOrCreate(
                ['company_id' => $company->id, 'identification_number' => $id],
                ['first_name' => $fn, 'last_name' => $ln, 'email' => $email, 'phone' => $phone]
            );
        }

        // Postulaciones en distintos estados
        $apps = [];
        $appData = [
            [$v1->id, $candidates[0]->id, 'interview',  '2026-09-05'],
            [$v1->id, $candidates[1]->id, 'reviewing',  '2026-09-06'],
            [$v1->id, $candidates[2]->id, 'new',        '2026-09-07'],
            [$v2->id, $candidates[3]->id, 'selected',   '2026-09-11'],
            [$v2->id, $candidates[4]->id, 'reviewing',  '2026-09-12'],
            [$v3->id, $candidates[5]->id, 'rejected',   '2026-08-20'],
            [$v3->id, $candidates[6]->id, 'selected',   '2026-08-22'],
            [$v1->id, $candidates[7]->id, 'new',        '2026-09-08'],
        ];
        foreach ($appData as [$vacId, $candId, $status, $date]) {
            $apps[] = JobApplication::firstOrCreate(
                ['company_id' => $company->id, 'job_vacancy_id' => $vacId, 'job_candidate_id' => $candId],
                ['applied_at' => $date, 'status' => $status]
            );
        }

        // 2 entrevistas
        JobInterview::firstOrCreate(
            ['company_id' => $company->id, 'job_application_id' => $apps[0]->id, 'scheduled_at' => '2026-09-15 10:00:00'],
            ['type' => 'presential', 'result' => 'passed', 'notes' => 'Excelente perfil técnico.', 'interviewer_id' => $interviewer?->id]
        );
        JobInterview::firstOrCreate(
            ['company_id' => $company->id, 'job_application_id' => $apps[3]->id, 'scheduled_at' => '2026-09-18 14:00:00'],
            ['type' => 'virtual', 'result' => 'passed', 'notes' => 'Candidato seleccionado para el cargo.', 'interviewer_id' => $interviewer?->id]
        );
    }
}
