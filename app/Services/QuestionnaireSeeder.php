<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionSite;
use App\Models\Questionnaire;
use App\Models\QuestionnaireField;
use App\Models\Season;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class QuestionnaireSeeder
{
    public function seedAll(): void
    {
        $this->seedDefinitions();
        $this->seedForExisting();
    }

    public function seedDefinitions(): void
    {
        foreach ($this->defaultQuestions() as $question) {
            Question::updateOrCreate(['id' => $question['id']], $question);
        }

        foreach ($this->defaultQuestionnaireFields() as $field) {
            QuestionnaireField::updateOrCreate(['id' => $field['id']], [
                'question' => $field['question'],
                'description' => $field['description'] ?? null,
                'slug' => $field['slug'],
                'options' => $field['options'] ?? [],
                'type_name' => $field['type_name'] ?? null,
                'conditional_logic' => $field['conditional_logic'],
                'type_input_id' => $field['type_input_id'],
                'type_data_id' => $field['type_data_id'],
            ]);
        }
    }

    public function seedForExisting(): void
    {
        Service::query()->each(function (Service $service) {
            $this->ensureQuestionSites($service);
        });

        Season::with('service')->each(function (Season $season) {
            $this->seedForSeason($season);
        });
    }

    public function seedForSeason(Season $season): void
    {
        $service = $season->service ?? Service::find($season->service_id);
        if (!$service) {
            return;
        }

        $this->seedDefinitions();
        $this->ensureQuestionSites($service);

        $questionnaireExists = Questionnaire::where('service_id', $service->id)
            ->where('season_id', $season->id)
            ->exists();
        if ($questionnaireExists) {
            return;
        }

        $teamId = $season->team_id ?? $service->team_id;
        $creatorId = User::where('team_id', $teamId)->value('id') ?? User::value('id');

        $data = [
            'name' => $this->defaultQuestionnaireName($service),
            'description' => $this->defaultQuestionnaireDescription(),
            'season_id' => $season->id,
            'service_id' => $service->id,
            'team_id' => $teamId,
        ];

        if (Schema::hasColumn('questionnaires', 'created_by_id')) {
            $data['created_by_id'] = $creatorId;
        }

        if (Schema::hasColumn('questionnaires', 'updated_by_id')) {
            $data['updated_by_id'] = $creatorId;
        }

        $questionnaire = Questionnaire::create($data);

        foreach ($this->defaultQuestionnaireFields() as $field) {
            $questionnaire->fields()->attach($field['id'], [
                'required' => (bool) ($field['required'] ?? false),
                'visable' => !($field['hidden'] ?? false),
                'sort_order' => $field['sort_order'] ?? 0,
            ]);
        }
    }

    private function ensureQuestionSites(Service $service): void
    {
        $existingSites = QuestionSite::where('service_id', $service->id)->exists();
        if ($existingSites) {
            return;
        }

        foreach ($this->defaultQuestionSites() as $site) {
            QuestionSite::updateOrCreate(
                [
                    'question_id' => $site['question_id'],
                    'service_id' => $service->id,
                ],
                [
                    'enabled' => $site['enabled'],
                    'sort_order' => $site['sort_order'],
                ]
            );
        }
    }

    private function defaultQuestionnaireName(Service $service): string
    {
        return $service->name . ' Questionnaire';
    }

    private function defaultQuestionnaireDescription(): ?string
    {
        return 'Canopy Tour Description';
    }

    private function defaultQuestionnaireFields(): array
    {
        return [
            [
                'id' => 1,
                'question' => 'Select your Lead Guide',
                'slug' => 'lead_guide_id',
                'type_input_id' => 14,
                'type_data_id' => 3,
                'options' => [],
                'type_name' => 'leadGuides',
                'required' => true,
                'hidden' => false,
                'sort_order' => 1,
                'conditional_logic' => null,
            ],
            [
                'id' => 2,
                'question' => 'Select your Safety Guide',
                'slug' => 'safety_guide_id',
                'type_input_id' => 15,
                'type_data_id' => 3,
                'options' => [],
                'type_name' => 'safetyGuides',
                'required' => true,
                'hidden' => false,
                'sort_order' => 2,
                'conditional_logic' => null,
            ],
            [
                'id' => 3,
                'question' => 'What kind of a group are you with?',
                'slug' => 'type_group_id',
                'type_input_id' => 16,
                'type_data_id' => 3,
                'options' => [],
                'type_name' => 'typeGroup',
                'required' => true,
                'hidden' => false,
                'sort_order' => 3,
                'conditional_logic' => null,
            ],
            [
                'id' => 4,
                'question' => 'Please enter your First and Last name',
                'slug' => 'full_name',
                'type_input_id' => 1,
                'type_data_id' => 1,
                'required' => true,
                'hidden' => false,
                'sort_order' => 4,
                'conditional_logic' => null,
            ],
            [
                'id' => 5,
                'question' => 'Age',
                'slug' => 'age',
                'type_input_id' => 19,
                'type_data_id' => 3,
                'required' => true,
                'hidden' => false,
                'sort_order' => 5,
                'conditional_logic' => null,
            ],
            [
                'id' => 6,
                'question' => 'Where did you hear about us',
                'slug' => 'referral_id',
                'type_input_id' => 17,
                'type_data_id' => 3,
                'options' => [],
                'required' => true,
                'hidden' => false,
                'sort_order' => 6,
                'conditional_logic' => null,
            ],
            [
                'id' => 7,
                'question' => 'Where are you from? Select your Continent',
                'slug' => 'continent_id',
                'type_input_id' => 11,
                'type_data_id' => 3,
                'options' => [],
                'type_name' => 'continents',
                'required' => true,
                'hidden' => false,
                'sort_order' => 7,
                'conditional_logic' => null,
            ],
            [
                'id' => 8,
                'question' => 'Country of Residence',
                'slug' => 'country_id',
                'type_input_id' => 12,
                'type_data_id' => 3,
                'options' => [],
                'type_name' => 'countries',
                'required' => true,
                'hidden' => false,
                'sort_order' => 8,
                'conditional_logic' => null,
            ],
            [
                'id' => 9,
                'question' => 'Select Province',
                'slug' => 'province_id',
                'type_input_id' => 13,
                'type_data_id' => 3,
                'options' => [],
                'type_name' => 'states',
                'required' => true,
                'hidden' => false,
                'sort_order' => 9,
                'conditional_logic' => null,
            ],
            [
                'id' => 10,
                'question' => 'Do you have any medical conditions we should be aware of?',
                'slug' => 'medical_id',
                'type_input_id' => 18,
                'type_data_id' => 3,
                'options' => ['Yes', 'No'],
                'type_name' => 'typeConfirmation',
                'required' => true,
                'hidden' => false,
                'sort_order' => 10,
                'conditional_logic' => null,
            ],
            [
                'id' => 11,
                'question' => 'Please let us know what medical conditions you have and confirm that your doctor has agreed that you may partake in this activity?',
                'slug' => 'medical_detail',
                'type_input_id' => 1,
                'type_data_id' => 2,
                'required' => true,
                'hidden' => false,
                'sort_order' => 11,
                'conditional_logic' => null,
            ],
            [
                'id' => 12,
                'question' => 'I am fit enough to walk 1km in steep terrain',
                'slug' => 'is_fit',
                'type_input_id' => 18,
                'type_data_id' => 3,
                'options' => ['Yes', 'No'],
                'type_name' => 'typeConfirmation',
                'required' => true,
                'hidden' => false,
                'sort_order' => 12,
                'conditional_logic' => null,
            ],
            [
                'id' => 13,
                'question' => 'Please agree to the following terms and conditions',
                'description' => 'By accessing and using this website, you acknowledge and agree to the terms outlined in this Indemnity Agreement. This form serves to protect the website owners, administrators, and affiliated parties from any liability arising from the use of the site, its content, or any services provided. By proceeding, you confirm that you understand and accept full responsibility for any risks associated with your use of this website and waive any claims against its owners for potential losses, damages, or liabilities.',
                'slug' => 'is_agree',
                'type_input_id' => 18,
                'type_data_id' => 3,
                'options' => ['Yes', 'No'],
                'required' => true,
                'hidden' => false,
                'sort_order' => 13,
                'conditional_logic' => null,
            ],
            [
                'id' => 14,
                'question' => 'Please enter your email address',
                'slug' => 'email_address',
                'type_input_id' => 1,
                'type_data_id' => 2,
                'required' => true,
                'hidden' => false,
                'sort_order' => 14,
                'conditional_logic' => null,
            ],
            [
                'id' => 15,
                'question' => 'Please sign below',
                'slug' => 'signature',
                'type_input_id' => 10,
                'type_data_id' => 8,
                'required' => true,
                'hidden' => false,
                'sort_order' => 15,
                'conditional_logic' => null,
            ],
            [
                'id' => 16,
                'question' => 'Phone Number',
                'slug' => 'phone_number',
                'type_input_id' => 1,
                'type_data_id' => 3,
                'required' => true,
                'hidden' => false,
                'sort_order' => 16,
                'conditional_logic' => null,
            ],
            [
                'id' => 17,
                'question' => 'Gender',
                'slug' => 'gender',
                'type_input_id' => 18,
                'type_data_id' => 3,
                'options' => ['Yes', 'No', 'N/A'],
                'required' => true,
                'hidden' => false,
                'sort_order' => 17,
                'conditional_logic' => null,
            ],
            [
                'id' => 18,
                'question' => 'Birth Date',
                'slug' => 'birth_date',
                'type_input_id' => 1,
                'type_data_id' => 1,
                'required' => true,
                'hidden' => false,
                'sort_order' => 18,
                'conditional_logic' => null,
            ],
            [
                'id' => 19,
                'question' => 'Is Minor',
                'slug' => 'is_minor',
                'type_input_id' => 18,
                'type_data_id' => 3,
                'options' => ['Yes', 'No'],
                'required' => true,
                'hidden' => false,
                'sort_order' => 19,
                'conditional_logic' => null,
            ],
            [
                'id' => 20,
                'question' => 'Recieve Invite',
                'slug' => 'recieve_invite',
                'type_input_id' => 18,
                'type_data_id' => 3,
                'options' => ['Yes', 'No'],
                'required' => true,
                'hidden' => false,
                'sort_order' => 20,
                'conditional_logic' => null,
            ],
            [
                'id' => 21,
                'question' => 'Helmet Size',
                'slug' => 'helmet_size',
                'type_input_id' => 7,
                'type_data_id' => 3,
                'options' => [7, 8, 9],
                'required' => true,
                'hidden' => false,
                'sort_order' => 21,
                'conditional_logic' => null,
            ],
            [
                'id' => 22,
                'question' => 'Pully Number',
                'slug' => 'pully_number',
                'type_input_id' => 1,
                'type_data_id' => 1,
                'required' => true,
                'hidden' => false,
                'sort_order' => 22,
                'conditional_logic' => null,
            ],
        ];
    }

    private function defaultQuestions(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Select your Lead Guide',
                'description' => null,
                'slug' => 'lead_guide_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 2,
                'name' => 'Select your Safety Guide',
                'description' => null,
                'slug' => 'safety_guide_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 3,
                'name' => 'Select group',
                'description' => null,
                'slug' => 'type_group_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 4,
                'name' => 'First + last name',
                'description' => null,
                'slug' => 'full_name',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 5,
                'name' => 'Age group',
                'description' => null,
                'slug' => 'age_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 6,
                'name' => 'Referral',
                'description' => null,
                'slug' => 'referral_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 7,
                'name' => 'Continent',
                'description' => null,
                'slug' => 'continent_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 8,
                'name' => 'Country',
                'description' => null,
                'slug' => 'country_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 9,
                'name' => 'Province (if SA)',
                'description' => null,
                'slug' => 'province_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 10,
                'name' => 'Medical',
                'description' => null,
                'slug' => 'medical_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 11,
                'name' => 'Medical conditions',
                'description' => null,
                'slug' => 'medical_detail',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 12,
                'name' => 'Fitness',
                'description' => null,
                'slug' => 'is_fit',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 13,
                'name' => 'Indemnity terms',
                'description' => null,
                'slug' => 'is_agree',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 14,
                'name' => 'Email',
                'description' => null,
                'slug' => 'email_address',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 15,
                'name' => 'Signature',
                'description' => null,
                'slug' => 'signature',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 16,
                'name' => 'Gender',
                'description' => null,
                'slug' => 'gender',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 17,
                'name' => 'Visitor type (individual, couple, etc.)',
                'description' => null,
                'slug' => 'visitor_type',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 18,
                'name' => 'Marketing source',
                'description' => null,
                'slug' => 'marketing_opt_in',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 19,
                'name' => 'Birthday month',
                'description' => null,
                'slug' => 'birthday_month',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 20,
                'name' => 'Birthday day',
                'description' => null,
                'slug' => 'birthday_day',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 21,
                'name' => 'Pregnancy',
                'description' => null,
                'slug' => 'pregnant',
                'options' => [],
                'conditional_logic' => ['rule' => 'female only'],
                'type' => 'indemnity',
            ],
            [
                'id' => 22,
                'name' => 'Guardian name',
                'description' => null,
                'slug' => 'parent_id',
                'options' => [],
                'conditional_logic' => ['rule' => 'if age 5-17'],
                'type' => 'indemnity',
            ],
            [
                'id' => 23,
                'name' => 'Lunch option',
                'description' => null,
                'slug' => 'lunch_option',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'indemnity',
            ],
            [
                'id' => 24,
                'name' => 'Customer ID',
                'description' => null,
                'slug' => 'customer_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 25,
                'name' => 'Guide ID',
                'description' => null,
                'slug' => 'guide_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 26,
                'name' => 'Reception rating',
                'description' => null,
                'slug' => 'reception_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 27,
                'name' => 'Safety briefing rating',
                'description' => null,
                'slug' => 'briefing_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 28,
                'name' => '4x4 experience rating',
                'description' => null,
                'slug' => 'experience_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 29,
                'name' => 'Guide knowledge rating',
                'description' => null,
                'slug' => 'knowledge_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 30,
                'name' => 'Safety comfort rating',
                'description' => null,
                'slug' => 'comfort_id',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
            [
                'id' => 31,
                'name' => 'Comments',
                'description' => null,
                'slug' => 'comment',
                'options' => [],
                'conditional_logic' => null,
                'type' => 'feedback',
            ],
        ];
    }

    private function defaultQuestionSites(): array
    {
        return [
            [
                'question_id' => 1,
                'enabled' => false,
                'sort_order' => 1,
            ],
            [
                'question_id' => 2,
                'enabled' => false,
                'sort_order' => 2,
            ],
            [
                'question_id' => 3,
                'enabled' => true,
                'sort_order' => 1,
            ],
            [
                'question_id' => 4,
                'enabled' => true,
                'sort_order' => 2,
            ],
            [
                'question_id' => 5,
                'enabled' => true,
                'sort_order' => 5,
            ],
            [
                'question_id' => 6,
                'enabled' => true,
                'sort_order' => 3,
            ],
            [
                'question_id' => 7,
                'enabled' => true,
                'sort_order' => 4,
            ],
            [
                'question_id' => 8,
                'enabled' => true,
                'sort_order' => 5,
            ],
            [
                'question_id' => 9,
                'enabled' => true,
                'sort_order' => 9,
            ],
            [
                'question_id' => 10,
                'enabled' => true,
                'sort_order' => 10,
            ],
            [
                'question_id' => 11,
                'enabled' => true,
                'sort_order' => 11,
            ],
            [
                'question_id' => 12,
                'enabled' => true,
                'sort_order' => 12,
            ],
            [
                'question_id' => 13,
                'enabled' => true,
                'sort_order' => 13,
            ],
            [
                'question_id' => 14,
                'enabled' => true,
                'sort_order' => 14,
            ],
            [
                'question_id' => 15,
                'enabled' => true,
                'sort_order' => 15,
            ],
            [
                'question_id' => 16,
                'enabled' => true,
                'sort_order' => 16,
            ],
            [
                'question_id' => 17,
                'enabled' => true,
                'sort_order' => 17,
            ],
            [
                'question_id' => 18,
                'enabled' => true,
                'sort_order' => 18,
            ],
            [
                'question_id' => 19,
                'enabled' => true,
                'sort_order' => 19,
            ],
            [
                'question_id' => 20,
                'enabled' => true,
                'sort_order' => 20,
            ],
            [
                'question_id' => 21,
                'enabled' => true,
                'sort_order' => 21,
            ],
            [
                'question_id' => 22,
                'enabled' => true,
                'sort_order' => 22,
            ],
            [
                'question_id' => 23,
                'enabled' => true,
                'sort_order' => 23,
            ],
            [
                'question_id' => 24,
                'enabled' => true,
                'sort_order' => 24,
            ],
            [
                'question_id' => 25,
                'enabled' => true,
                'sort_order' => 25,
            ],
            [
                'question_id' => 26,
                'enabled' => true,
                'sort_order' => 26,
            ],
            [
                'question_id' => 27,
                'enabled' => true,
                'sort_order' => 27,
            ],
            [
                'question_id' => 28,
                'enabled' => true,
                'sort_order' => 28,
            ],
            [
                'question_id' => 29,
                'enabled' => true,
                'sort_order' => 29,
            ],
            [
                'question_id' => 30,
                'enabled' => true,
                'sort_order' => 30,
            ],
            [
                'question_id' => 31,
                'enabled' => true,
                'sort_order' => 31,
            ],
        ];
    }
}
