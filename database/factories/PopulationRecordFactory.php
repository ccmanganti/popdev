<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PopulationRecord>
 */
class PopulationRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function getEnumeratorInfo(){
        return User::whereHas(
            'roles', function($q){
                $q->where('name', 'Enumerator');
            })->get()[4];
            // Change the index number to change enumerator
            // Strictly for Admins, Programmers and Testers only
    }
    
    public function getMaritalStatus($fakeAge){
        if($fakeAge < 17){
            return '1';
        } else {
            return strval(fake()->numberBetween(1, 7));
        }
    }

    public function generateIndividuals($random_individual_num){
        $individuals = [];
        for ($data = 0; $data < $random_individual_num; $data++) {
            $fakeAge = fake()->numberBetween(1, 85);
            array_push($individuals, [
                "q1_last_name" => fake()->lastName(),
                "q1_first_name" => fake()->firstName(),
                "q1_middle_name" => fake()->lastName(),
                "q2" => strval(fake()->numberBetween(1, 22)),
                "q3" => strval(fake()->numberBetween(1, 2)),
                "q4" => strval($fakeAge),
                "q5" => strval(fake()->date('Y-m-d')),
                "q6" => "1",
                "q6_birth_province" => null,
                "q6_birth_city" => null,
                "q7" => "1",
                "q8" => $this->getMaritalStatus($fakeAge),
                "q9" => strval(fake()->numberBetween(1, 11)),
                "q9_others" => null,
                "q10" => strval(fake()->numberBetween(1, 14)),
                "q11" => strval(fake()->numberBetween(0, 13)),
                "q12" => "3",
                "q13" => null,
                "q14" => null,
                "q15" => strval(fake()->numberBetween(4000, 100000)),
                "q16" => "6",
                "q17" => null,
                "q18" => strval(fake()->numberBetween(0, 9)),
                "q19" => strval(fake()->numberBetween(1, 2)),
                "q20" => strval(fake()->numberBetween(1, 2)),
                "q21" => strval(fake()->numberBetween(1, 3)),
                "q22" => strval(fake()->numberBetween(1, 2)),
                "q23" => fake()->sentence(3),
                "q24" => fake()->sentence(1)
            ]);
        }
        return $individuals;
    }

    public function definition(): array
    {
        $random_individual_num = rand(1, 10);

        return [
            'household_number' => fake()->randomNumber(4),
            'province' => $this->getEnumeratorInfo()->province,
            'city_or_municipality' => $this->getEnumeratorInfo()->city_or_municipality,
            'barangay' => $this->getEnumeratorInfo()->barangay,
            'address_1' => fake()->sentence(3),
            'address_2' => fake()->sentence(2),
            'name_of_respondent' => fake()->name(),
            'household_head' => fake()->name(),
            'household_members_total' => strval($random_individual_num),
            'individual_record' => $this->generateIndividuals($random_individual_num),
            'q25' => strval(fake()->numberBetween(1, 2)),
            'q26' => strval(fake()->numberBetween(1, 2)),
            'q27' => strval(fake()->numberBetween(1, 3)),
            'q28' => strval(fake()->numberBetween(1, 6)),
            'q29' => strval(fake()->numberBetween(1, 4)),
            'q30' => strval(fake()->numberBetween(1, 2)),
            'q31' => strval(fake()->numberBetween(1, 11)),
            'q32' => strval(fake()->numberBetween(1, 10)),
            'q33' => strval(fake()->numberBetween(1, 10)),
            'encoder_name' => $this->getEnumeratorInfo()->name,
                // CHANGE THE INDEX TO CHANGE ENUMERATOR OWNERSHIP
        ];
    }
}
