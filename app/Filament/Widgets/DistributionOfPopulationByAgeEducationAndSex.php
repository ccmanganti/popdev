<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\PopulationRecord; 
use App\Models\PhilProvince; 
use App\Models\PhilMuni; 
use App\Models\Philbrgy; 
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Closure;
use Illuminate\Support\Facades\Route;


class DistributionOfPopulationByAgeEducationAndSex extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */

     public static function canView(): bool
     {
         if ($currentPath= Route::getFacadeRoot()->current()->uri() == "/"){
             return false;
         } else {
             return true;
         }
     }
    protected static ?int $sort = 7; 

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static string $chartId = 'distributionOfPopulationByAgeEducationAndSex';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'DistributionOfPopulationByAgeEducationAndSex';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

     protected function getFormSchema(): array
    {
        $user = auth()->user();
        if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            return [

            ];
        }
        return [
            Select::make('province')
                ->reactive()
                ->default(function() use ($user){
                    if ($user->getRoleNames()->first() !== 'Superadmin'){
                        return Philprovince::where('provCode', '=', $user->province)->first()->provCode; 
                    } else {
                        return false;
                    }
                })
                ->label('Province Name')
                ->options(function() use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                    } else if ($user->getRoleNames()->first() === 'Barangay'){
                        return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                    } else {
                        return Philprovince::all()->pluck('provDesc', 'provCode');
                    }
                }),
            Select::make('city_or_municipality')
                ->reactive()
                ->default(function() use ($user){
                    if ($user->getRoleNames()->first() !== 'Superadmin'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->first()->citymunCode; 
                    } else {
                        return false;
                    }   
                })
                ->disabled(fn (Closure $get) => ($get('province') == null))
                ->label('City/Municipality Name')
                ->options(function(callable $get) use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                    } else if ($user->getRoleNames()->first() === 'Barangay'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                    } else {
                        return Philmuni::where('provCode', '=', $get('province'))->pluck('citymunDesc', 'citymunCode');
                    }
                }),
            Select::make('barangay')
                ->reactive()
                ->label('Barangay Name')
                ->disabled(fn (Closure $get) => ($get('province') == null))
                ->options(function(callable $get) use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philbrgy::where('citymunCode', '=', $user->city_or_municipality)->pluck('brgyDesc', 'brgyCode'); 
                    } else {
                        return Philbrgy::where('citymunCode', '=', $get('city_or_municipality'))->pluck('brgyDesc', 'brgyCode');
                    }
                })
        ];
    }

    public function getPRAgeEduAndSex($ageAndSex) {
        $user = auth()->user();
        $json_data = PopulationRecord::all();
        $json_data_count = 0;
        foreach($json_data as $json){
            
            // SHOW DATA DEPENING ON USER ACCESS AND ROLES
                foreach($json->individual_record as $data){
                
                    if($user->hasRole('Superadmin')){
                        if(($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6]) && $ageAndSex[3] == null && $ageAndSex[4] == null && $ageAndSex[5] == null){
                            $json_data_count += 1;
                        } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6]) && ($json->province == $ageAndSex[3]) && $ageAndSex[4] == null && $ageAndSex[5] == null){
                            $json_data_count += 1;
                        } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6]) && ($json->province == $ageAndSex[3]) && ($json->city_or_municipality == $ageAndSex[4]) && $ageAndSex[5] == null){
                            $json_data_count += 1;
                        } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6]) && ($json->province == $ageAndSex[3]) && ($json->city_or_municipality == $ageAndSex[4]) && ($json->barangay == $ageAndSex[5])){
                            $json_data_count += 1;
                        }

                    } else if($user->hasRole('LGU')){
                        if((($json->province) == $user->province) && (($json->city_or_municipality) == $user->city_or_municipality)){
                            if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6]) && ($json->city_or_municipality == $ageAndSex[4]) && $ageAndSex[5] == null){
                                $json_data_count += 1;
                            } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6]) && ($json->city_or_municipality == $ageAndSex[4]) && ($json->barangay == $ageAndSex[5])){
                                $json_data_count += 1;
                            }
                        }
                    } else if($user->hasRole('Enumerator') || $user->hasRole('Barangay')){
                        if((($json->province) == $user->province) && (($json->city_or_municipality) == $user->city_or_municipality) && (($json->barangay) == $user->barangay)){
                            if(($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($data['q11'] == $ageAndSex[6])){
                                $json_data_count += 1;
                            }
                        }
                    }
            }
        }
        return $json_data_count;
    }

    // public bool $readyToLoad = false;

    // public function loadData(){
    //     $this->readyToLoad = true;
    // }

    protected function getOptions(): array
    {

        // if (! $this->readyToLoad) {
        //     return [
        //         Card::make('Total', 'loading...'),
        //     ];
        // }

        // sleep(60);

        if (auth()->user()->hasRole('Barangay') || auth()->user()->hasRole('Enumerator')){
            $province = null;
            $city_or_municipality = null;
            $barangay = null;
            
        } else {
            $activeFilter = $this->filter;
            $province = $this->filterFormData['province'];
            $city_or_municipality = $this->filterFormData['city_or_municipality'];
            $barangay = $this->filterFormData['barangay'];    
        }


        
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 600,
                'stacked' => true,
            ],
            'series' => [
                // [
                //     'name' => 'No Education',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '0']),
                //     ],
                // ],
                // [
                //     'name' => 'Pre-School',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '1']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '1']),
                //     ],
                // ],
                // [
                //     'name' => 'Elementary Level',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '2']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '2']),
                //     ],
                // ],
                // [
                //     'name' => 'Elementary Graduate',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '3']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '3']),
                //     ],
                // ],
                // [
                //     'name' => 'Highschool Level',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '4']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '4']),
                //     ],
                // ],
                // [
                //     'name' => 'Highschool Graduate',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '5']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '5']),
                //     ],
                // ],
                // [
                //     'name' => 'JHS Level',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '6']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '6']),
                //     ],
                // ],
                // [
                //     'name' => 'JHS Graduate',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '7']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '7']),
                //     ],
                // ],
                // [
                //     'name' => 'SHS Level',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '8']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '8']),
                //     ],
                // ],
                // [
                //     'name' => 'SHS Graduate',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '9']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '9']),
                //     ],
                // ],
                // [
                //     'name' => 'Vocational/Tech',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '10']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '10']),
                //     ],
                // ],
                // [
                //     'name' => 'College Level',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '11']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '11']),
                //     ],
                // ],
                // [
                //     'name' => 'College Graduate',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '12']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '12']),
                //     ],
                // ],
                // [
                //     'name' => 'Post-Graduate',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '13']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '13']),
                //     ],
                // ],


                // [
                //     'name' => 'No-Education',
                //     'data' => [
                //         $this->getPRAgeEduAndSex([0, 4,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([5, 9,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([10, 14,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([15, 19,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([20, 24,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([25, 29,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([30, 34,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([35, 39,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([40, 44,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([45, 49,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([50, 54,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([55, 59,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([60, 64,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([65, 69,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([70, 74,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([75, 79,'1', $province, $city_or_municipality, $barangay, '0']),
                //         $this->getPRAgeEduAndSex([80, 150,'1', $province, $city_or_municipality, $barangay, '0']),
                //     ],
                // ],
                // [
                //     'name' => 'Pre-school',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '1']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '1']),
                //     ],
                // ],
                // [
                //     'name' => 'Elementary Level',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '2']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '2']),
                //     ],
                // ],
                // [
                //     'name' => 'Elementary Graduate',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '3']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '3']),
                //     ],
                // ],
                // [
                //     'name' => 'Highschool Level',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '4']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '4']),
                //     ],
                // ],
                // [
                //     'name' => 'Highschool Graduate',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '5']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '5']),
                //     ],
                // ],
                // [
                //     'name' => 'JHS Level',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '6']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '6']),
                //     ],
                // ],
                // [
                //     'name' => 'JHS Graduate',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '7']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '7']),
                //     ],
                // ],
                // [
                //     'name' => 'SHS Level',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '8']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '8']),
                //     ],
                // ],
                // [
                //     'name' => 'SHS Graduate',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '9']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '9']),
                //     ],
                // ],
                // [
                //     'name' => 'Vocational/Tech',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '10']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '10']),
                //     ],
                // ],
                // [
                //     'name' => 'College Level',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '11']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '11']),
                //     ],
                // ],
                // [
                //     'name' => 'College Graduate',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '12']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '12']),
                //     ],
                // ],
                // [
                //     'name' => 'Post-Graduate',
                //     'data' => [
                //         -$this->getPRAgeEduAndSex([0, 4,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([5, 9,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([10, 14,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([15, 19,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([20, 24,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([25, 29,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([30, 34,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([35, 39,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([40, 44,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([45, 49,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([50, 54,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([55, 59,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([60, 64,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([65, 69,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([70, 74,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([75, 79,'2', $province, $city_or_municipality, $barangay, '13']),
                //         -$this->getPRAgeEduAndSex([80, 150,'2', $province, $city_or_municipality, $barangay, '13']),
                //     ],
                // ],
            ],
            'xaxis' => [
                'categories' => [
                    '00-04', 
                    '05-09', 
                    '10-14', 
                    '15-19',
                    '20-24',
                    '25-29',
                    '30-34',
                    '35-39',
                    '40-44',
                    '45-49',
                    '50-54',
                    '55-59',
                    '60-64',
                    '65-69',
                    '70-74',
                    '75-79',
                    '80+',
                ],
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                    'barHeight' => '85%',
                    'dataLabels' => [
                        'total' => [
                            'enabled' => false,
                            'style' => [
                                'color' => 'gray'
                            ]
                            ],
                            'position' => 'top'
                    ]
                ],
            ],
            'legend' => [
                'customLegendItems' => [
                    'No Education',
                    'Pre-school',
                    'Elementary Level',
                    'Elementary Graduate',
                    'Highschool level',
                    'Highschool Graduate',
                    'Junior HS level',
                    'Junior HS Graduate',
                    'Senior HS Level',
                    'Senior HS Graduate',
                    'Vocational/Tech',
                    'College Level',
                    'College Graduate',
                    'Post-Graduate',
                ]
            ],
            'colors' => [
                '#f76262',
                '#ff7753',
                '#ff8e45',
                '#ffa638',
                '#fabf31',
                '#ecd735',
                '#d9ef48',
                '#62f77e',
                '#00bfff',
                '#6848ef',
                '#079d66',
                '#65c85d',
                '#baef48',
            ],
        ];
    }
}
