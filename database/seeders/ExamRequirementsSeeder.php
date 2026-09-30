<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Catalog\Models\ExamRequirement;
use Illuminate\Database\Seeder;

/**
 * Requisitos preanalíticos (solo preparación clínica) para los 46 exámenes del CatalogSeeder.
 *
 * Al ejecutarse, reemplaza todos los requisitos de cada examen listado (no acumula textos viejos).
 * Orientación habitual de laboratorio/imagen; validar con protocolo interno de la clínica.
 *
 *   php artisan db:seed --class=ExamRequirementsSeeder --force
 */
class ExamRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;
        $replacedExams = 0;
        $missingExams = [];

        foreach ($this->requirementDefinitions() as $examName => $descriptions) {
            $exam = Exam::query()->where('name', $examName)->first();

            if ($exam === null) {
                $missingExams[] = $examName;

                continue;
            }

            $deleted = ExamRequirement::query()->where('exam_id', $exam->id)->delete();

            if ($deleted > 0 || $descriptions !== []) {
                $replacedExams++;
            }

            foreach ($descriptions as $description) {
                ExamRequirement::query()->create([
                    'exam_id' => $exam->id,
                    'description' => $description,
                ]);
                $created++;
            }
        }

        $this->command?->info("✅ ExamRequirementsSeeder: {$replacedExams} examen(es) actualizados, {$created} requisito(s) clínicos cargados.");

        if ($missingExams !== []) {
            $this->command?->warn('Exámenes no encontrados (revise nombres vs CatalogSeeder): '.implode(', ', $missingExams));
        }
    }

    /**
     * Solo requisitos de preparación del estudio (sin orden médica ni documento de identidad).
     *
     * @return array<string, list<string>>
     */
    protected function requirementDefinitions(): array
    {
        return [
            // ── Hematología ─────────────────────────────────────────────────────
            'Hemograma completo' => [
                'En adultos no requiere ayuno, salvo indicación médica.',
                'Evitar ejercicio intenso y sauna 24 horas antes de la extracción.',
                'Informar si presenta fiebre, infección activa o tratamiento con quimioterapia.',
            ],
            'Hemoglobina y hematocrito' => [
                'No requiere ayuno en la mayoría de los casos.',
                'Evitar suplementos de hierro 24 horas antes, salvo que el médico indique lo contrario.',
                'Informar si hay menstruación abundante o sangrado reciente.',
            ],
            'Velocidad de sedimentación globular (VSG)' => [
                'No requiere ayuno.',
                'Evitar ejercicio intenso 24 horas antes.',
                'Informar enfermedad febril o inflamatoria activa al momento de la toma.',
            ],
            'Recuento de plaquetas' => [
                'No requiere ayuno.',
                'Evitar medicamentos que afecten plaquetas solo si el médico lo autoriza (ej. aspirina).',
                'Informar si hay moretones espontáneos o sangrado gingival frecuente.',
            ],

            // ── Bioquímica Clínica ────────────────────────────────────────────────
            'Glucosa en ayunas' => [
                'Ayuno de 8 a 12 horas; solo se permite agua.',
                'No fumar ni mascar chicle durante el ayuno.',
                'Preferible extracción entre 7:00 y 10:00.',
            ],
            'Urea y creatinina' => [
                'Ayuno de 8 horas recomendado.',
                'Mantener hidratación habitual el día previo.',
                'Informar dieta muy rica en proteínas o deshidratación reciente.',
            ],
            'Perfil hepático (TGO, TGP, bilirrubinas)' => [
                'Ayuno de 8 a 12 horas.',
                'Evitar alcohol 48 horas antes del estudio.',
                'Informar medicamentos hepatotóxicos o suplementos herbarios recientes.',
            ],
            'Ácido úrico' => [
                'Ayuno de 8 a 12 horas.',
                'Evitar alcohol y comidas muy ricas en purinas 24 horas antes.',
                'Informar crisis de gota reciente o diuréticos en uso.',
            ],
            'Proteínas totales y albúmina' => [
                'Ayuno de 8 a 12 horas.',
                'Permanece hidratado; evitar ayuno prolongado excesivo sin indicación médica.',
            ],

            // ── Perfil Lipídico ──────────────────────────────────────────────────
            'Colesterol total' => [
                'Ayuno de 12 horas (solo agua).',
                'Evitar alcohol 48 horas antes.',
                'Mantener dieta habitual 3 días previos; no realizar dieta estricta salvo indicación.',
            ],
            'HDL colesterol' => [
                'Ayuno de 12 horas (solo agua).',
                'Evitar alcohol 48 horas antes.',
            ],
            'LDL colesterol' => [
                'Ayuno de 12 horas (solo agua).',
                'Evitar alcohol 48 horas antes.',
            ],
            'Triglicéridos' => [
                'Ayuno de 12 horas (solo agua).',
                'Evitar alcohol 48 horas antes.',
                'Evitar comidas muy grasas 24 horas antes del estudio.',
            ],
            'Perfil lipídico completo' => [
                'Ayuno de 12 horas (solo agua).',
                'Evitar alcohol 48 horas antes.',
                'No suspender estatinas ni hipolipemiantes sin indicación del médico.',
            ],

            // ── Coagulación ─────────────────────────────────────────────────────
            'Tiempo de protrombina (TP / INR)' => [
                'Informar uso de warfarina, acenocumarol, heparina o antiagregantes.',
                'No suspender anticoagulantes sin autorización del médico tratante.',
                'Si es control de INR, intentar siempre a la misma hora y condiciones.',
            ],
            'TTPA (tiempo parcial de tromboplastina)' => [
                'Informar heparina de bajo peso molecular o heparina no fraccionada.',
                'No suspender anticoagulantes sin indicación médica.',
                'Evitar extracción en brazo con acceso venoso central o infusión en ese brazo.',
            ],
            'Fibrinógeno' => [
                'Informar anticoagulantes, antiagregantes y proceso inflamatorio activo.',
                'En mujeres, indicar si hay menstruación o sangrado uterino abundante.',
            ],
            'Tiempo de sangría' => [
                'Evitar aspirina y AINEs 48 horas antes, salvo indicación médica.',
                'No realizar el estudio sobre heridas o zonas inflamadas.',
                'Informar trastornos hemorrágicos conocidos o moretones frecuentes.',
            ],

            // ── Uroanálisis ─────────────────────────────────────────────────────
            'Examen general de orina' => [
                'Recolectar primera orina de la mañana.',
                'Realizar aseo de genitales con agua; desechar el primer chorro.',
                'Recolectar muestra media en frasco estéril sin tocar el interior.',
            ],
            'Urocultivo con antibiograma' => [
                'Primera orina de la mañana sin chorro inicial.',
                'Limpieza cuidadosa de zona genital antes de la recolección.',
                'Informar antibióticos en curso o tomados en los últimos 7 días.',
                'Entregar muestra al laboratorio en menos de 2 horas o refrigerar según indicación.',
            ],
            'Microalbuminuria en orina' => [
                'Preferible primera orina de la mañana.',
                'Evitar ejercicio intenso 24 horas antes de la recolección.',
                'Informar infección urinaria reciente o menstruación activa.',
            ],
            'Creatinina en orina de 24 horas' => [
                'Descartar la primera micción del día; a partir de ahí recolectar todo durante 24 horas.',
                'Conservar el frasco refrigerado durante todo el período de recolección.',
                'Al finalizar, anotar hora de inicio y fin en el frasco.',
            ],

            // ── Serología e Inmunología ─────────────────────────────────────────
            'VDRL / RPR (sífilis)' => [
                'No requiere ayuno.',
                'Informar tratamiento previo para sífilis y fecha aproximada de exposición si la conoce.',
            ],
            'HIV ELISA (VIH)' => [
                'No requiere ayuno.',
                'Ventana inmunológica: consultar con el médico si la exposición fue menor a 3 meses.',
                'Informar tratamiento antirretroviral si está en uso.',
            ],
            'HBsAg (Hepatitis B)' => [
                'No requiere ayuno.',
                'Informar vacunación contra hepatitis B y contacto con portadores conocidos.',
            ],
            'Anti-HCV (Hepatitis C)' => [
                'No requiere ayuno.',
                'Informar transfusiones, tatuajes o procedimientos invasivos previos si aplica.',
            ],
            'Proteína C reactiva (PCR)' => [
                'No requiere ayuno.',
                'Informar fiebre, infección o proceso inflamatorio activo al momento de la toma.',
            ],

            // ── Hormonas y Tiroides ─────────────────────────────────────────────
            'TSH (hormona estimulante de tiroides)' => [
                'No requiere ayuno.',
                'Informar uso de levotiroxina; si la toma, extraer muestra antes de la dosis diaria o según indicación médica.',
                'Evitar biotina en dosis altas 48 horas antes (puede alterar resultados).',
            ],
            'T4 libre' => [
                'No requiere ayuno.',
                'Informar levotiroxina o antitiroideos; coordinar hora de toma con el médico.',
                'Evitar biotina en dosis altas 48 horas antes.',
            ],
            'T3 total' => [
                'No requiere ayuno.',
                'Informar medicación tiroidea y suplementos con yodo o biotina.',
            ],
            'Prolactina' => [
                'Extracción preferible entre 8:00 y 10:00, en reposo 20–30 minutos antes.',
                'Evitar estrés, palpación mamaria y relaciones sexuales 12 horas antes.',
                'Informar uso de antipsicóticos u otros fármacos que eleven prolactina.',
            ],
            'Cortisol matutino' => [
                'Extracción entre 7:00 y 9:00.',
                'Reposo mínimo de 30 minutos antes de la venopunción.',
                'Informar uso de corticoides tópicos, inhalados u orales.',
            ],

            // ── Radiología Convencional ─────────────────────────────────────────
            'Radiografía de tórax PA y lateral' => [
                'Retirar cadenas, botones metálicos y sostén con varilla en la zona torácica.',
                'Informar posibilidad de embarazo.',
                'Mantener inspiración según indicación del técnico durante la toma.',
            ],
            'Radiografía de columna lumbar AP y lateral' => [
                'Retirar cinturones, hebillas y objetos metálicos en cintura y cadera.',
                'Informar posibilidad de embarazo.',
                'Informar cirugía de columna o implantes metálicos previos.',
            ],
            'Radiografía de cráneo' => [
                'Retirar aretes, piercings, gafas y prótesis removibles en cabeza.',
                'Informar posibilidad de embarazo.',
                'Informar traumatismo craneal reciente si aplica.',
            ],
            'Radiografía de pelvis' => [
                'Retirar objetos metálicos en cadera y bolsillos.',
                'Informar posibilidad de embarazo.',
                'En mujeres, informar fecha de última menstruación si hay sospecha de embarazo.',
            ],
            'Radiografía de mano' => [
                'Retirar anillos, pulseras y vendajes en la mano a estudiar.',
                'Informar fractura o cirugía reciente en esa extremidad.',
            ],

            // ── Ecografía ───────────────────────────────────────────────────────
            'Ecografía abdominal total' => [
                'Ayuno de 6 a 8 horas.',
                'Evitar alimentos grasos y gases 24 horas antes (bebidas carbonatadas, legumbres).',
                'Puede requerir vejiga moderadamente llena según indicación del médico.',
            ],
            'Ecografía renal' => [
                'Ayuno de 4 a 6 horas recomendado.',
                'Beber 2 vasos de agua 1 hora antes si se requiere evaluar vías urinarias.',
            ],
            'Ecografía pélvica' => [
                'Vejiga llena: ingerir 4 a 6 vasos de agua 1 hora antes y no orinar hasta el estudio.',
                'En evaluación ginecológica, evitar relaciones sexuales 24 horas antes si es posible.',
            ],
            'Ecografía obstétrica' => [
                'En primer trimestre, vejiga llena según indicación (agua 1 hora antes).',
                'Traer fecha de última menstruación o ecografías previas del embarazo.',
                'Usar ropa cómoda que permita exponer abdomen bajo.',
            ],
            'Ecografía de tiroides' => [
                'No requiere ayuno.',
                'Evitar collares gruesos y prendas ajustadas en el cuello.',
                'Informar nódulos conocidos o cirugía tiroidea previa.',
            ],

            // ── Tomografía Computarizada ────────────────────────────────────────
            'TAC de cráneo simple' => [
                'Retirar objetos metálicos en cabeza y cuello (piercings, clips).',
                'Informar alergia a contrastes yodados y posibilidad de embarazo.',
                'Informar antecedente de epilepsia o claustrofobia si aplica.',
            ],
            'TAC de tórax' => [
                'Retirar objetos metálicos en tórax (joyería, sujetadores con aro).',
                'Informar alergia a contrastes y posibilidad de embarazo.',
                'Coordinar suspensión de metformina tras contraste si el médico lo indica.',
            ],
            'TAC de abdomen' => [
                'Ayuno de 4 a 6 horas antes del estudio.',
                'Informar alergia a contrastes yodados, asma y función renal reducida.',
                'Informar posibilidad de embarazo.',
                'Hidratación adecuada si se administrará contraste endovenoso.',
            ],
            'TAC de columna lumbar' => [
                'Retirar cinturones y objetos metálicos en zona lumbar.',
                'Informar alergia a contrastes y posibilidad de embarazo.',
                'Informar cirugía previa con tornillos o barras en columna.',
            ],
        ];
    }
}
