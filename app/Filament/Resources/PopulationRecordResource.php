<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PopulationRecordResource\Pages;
use App\Filament\Resources\PopulationRecordResource\RelationManagers;
use App\Models\PopulationRecord;
use App\Models\User;
use App\Models\Role;
use App\Models\Philprovince;
use App\Models\Philmuni;
use App\Models\Philbrgy;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Closure;
use Filament\Tables\Columns\ImageColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class PopulationRecordResource extends Resource
{
    protected static ?string $model = PopulationRecord::class;

    protected static ?int $navigationSort = 1;

    // protected static ?string $recordTitleAttribute = 'household_head';
    protected static ?string $recordTitleAttribute = 'household_head';
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'household_number',
            'province',
            'city_or_municipality',
            'barangay',
            'address_1',
            'address_2',
            'name_of_respondent',
            'household_head',
            'encoder_name',
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Records Management';


    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([

                // HOUSEHOLD RECORD SECTION
                Section::make('Household Record')->schema([
                    TextInput::make('household_number')
                        ->numeric()
                        ->required()
                        ->minvalue(1)
                        ->maxLength(10)
                        ->unique(ignorable: fn ($record) => $record),

                    Select::make('province')
                        ->required()
                        ->label('Province Name')
                        ->options(function() use ($user) {
                            if ($user->getRoleNames()->first() === 'Enumerator'){
                                return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                            } else {
                                return Philprovince::all()->pluck('provDesc', 'provCode');
                            }
                        })
                        ->default(function() use ($user) {
                            if ($user->getRoleNames()->first() === 'Enumerator'){
                                return Philprovince::where('provCode', '=', $user->province)->first()->provCode; 
                            } else {
                                return false;
                            }
                        })
                        ->disabled(),

                    Select::make('city_or_municipality')
                    ->required()
                    ->label('City/Municipality Name')
                    ->options(function(callable $get) use ($user) {
                        if ($user->getRoleNames()->first() === 'Enumerator'){
                            return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                        } else {
                            return Philmuni::where('provCode', '=', $get('province'))->pluck('citymunDesc', 'citymunCode');
                        }
                    })
                    ->default(function() use ($user) {
                        if ($user->getRoleNames()->first() === 'Enumerator'){
                            return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->first()->citymunCode; 
                        } else {
                            return false;
                        }
                    })
                    ->disabled(),

                    Select::make('barangay')
                    ->required()
                    ->label('Barangay Name')
                    ->options(function(callable $get) use ($user) {
                        if ($user->getRoleNames()->first() === 'Enumerator'){
                            return Philbrgy::where('brgyCode', '=', $user->barangay)->pluck('brgyDesc', 'brgyCode'); 
                        } else {
                            return Philbrgy::where('citymunCode', '=', $get('city_or_municipality'))->pluck('brgyDesc', 'brgyCode');
                        }
                    })
                    ->default(function() use ($user) {
                        if ($user->getRoleNames()->first() === 'Enumerator'){
                            return Philbrgy::where('brgyCode', '=', $user->barangay)->first()->brgyCode; 
                        } else {
                            return false;
                        }
                    })
                    ->disabled(),

                    TextInput::make('address_1')
                        ->required()
                        ->maxLength(255)
                        ->label('Address Line 1'),

                    TextInput::make('address_2')
                        ->maxLength(255)
                        ->label('Address Line 2'),
                    
                    TextInput::make('name_of_respondent')
                        ->required()
                        ->maxLength(255)
                        ->label('Name of Respondent'),

                    TextInput::make('household_head')
                        ->required()
                        ->maxLength(255)
                        ->label('Name of Household Head'),

                    TextInput::make('household_members_total')
                        ->numeric()
                        ->reactive()
                        ->required()
                        ->minvalue(1)
                        ->maxLength(255)
                        ->label('Total Household Members')
                        ,
                ])
                ->collapsible()
                ->columns(2),

                // INDIVIDUAL RECORD
                Section::make('Individual Record')->schema([

                    Repeater::make('individual_record')
                        ->schema([
                            // Q1 TO Q14
                            Section::make('I. Q1 to Q14')
                                ->schema([
                                    TextInput::make('q1_last_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Q1.1 Last Name'),

                                    TextInput::make('q1_first_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Q1.2 First Name'),

                                    TextInput::make('q1_middle_name')
                                    ->maxLength(255)
                                    ->label('Q1.3 Middle Name'),

                                    Select::make('q2')
                                    ->required()
                                    ->label('Q2. Relationship to Household Head')
                                    ->options([
                                        '1' => '1 - Head',
                                        '2' => '2 - Spuse',
                                        '3' => '3 - Son',
                                        '4' => '4 - Daughter',
                                        '5' => '5 - Stepson',
                                        '6' => '6 - Stepdaughter',
                                        '7' => '7 - Son-in-Law',
                                        '8' => '8 - Daughter-in-Law',
                                        '9' => '9 - Grandson',
                                        '10' => '10 - Granddaughter',
                                        '11' => '11 - Father',
                                        '12' => '12 - Mother',
                                        '13' => '13 - Brother',
                                        '14' => '14 - Sister',
                                        '15' => '15 - Uncle',
                                        '16' => '16 - Aunt',
                                        '17' => '17 - Nephew',
                                        '18' => '18 - Niece',
                                        '19' => '19 - Other Relative',
                                        '20' => '20 - Non-relative',
                                        '21' => '21 - Boarder',
                                        '22' => '22 - Domestic helper',
                                    ]),

                                    Select::make('q3')
                                    ->required()
                                    ->label('Q3. Sex')
                                    ->options([
                                        '1' => '1 - Male',
                                        '2' => '2 - Female',
                                    ]),

                                    TextInput::make('q4')
                                    ->required()
                                    ->numeric()->minvalue(0)->maxValue(150)
                                    ->maxLength(3)
                                    ->label('Q4. Age'),

                                    DatePicker::make('q5')
                                    ->required()
                                    ->minDate(now()->subYears(150))
                                    ->maxDate(now())
                                    ->label('Q5. Date of Birth'),

                                    Select::make('q6')
                                    ->required()
                                    ->reactive()
                                    ->default('1')
                                    ->label('Q6. Place of Birth')
                                    ->options([
                                        '1' => '1 - '.Philmuni::where('citymunCode', '=', $user->city_or_municipality)->get()[0]->citymunDesc,
                                        '2' => '2 - Others',
                                    ]),

                                    Select::make('q6_birth_province')
                                    ->reactive()
                                    ->required()
                                    ->label('Q6.1 Specify Birth Place - Province')
                                    ->hidden(fn (Closure $get) => $get('q6') != '2')
                                    ->options(function(callable $get) use ($user) {
                                        return Philprovince::orderBy("provDesc")->pluck('provDesc', 'provCode');
                                    }),

                                    Select::make('q6_birth_city')
                                    ->reactive()
                                    ->required()
                                    ->label('Q6.2 Specify Birth Place - City or Municipality')
                                    ->hidden(fn (Closure $get) => $get('q6') != 2)
                                    ->disabled(fn (Closure $get) => $get('q6_birth_province') == null)
                                    ->options(function(callable $get) use ($user) {
                                        return Philmuni::orderBy("citymunDesc")->where('provCode', '=', $get('q6_birth_province'))->pluck('citymunDesc', 'citymunCode');
                                    }),

                                    Select::make('q7')
                                    ->required()
                                    ->default('1')
                                    ->label('Q7. Nationality')
                                    ->options([
                                        '1' => '1 - Filipino',
                                        '2' => '2 - Non-Filipino',
                                    ]),

                                    Select::make('q8')
                                    ->required()
                                    ->default('1')
                                    ->label('Q8. Marital Status')
                                    ->options([
                                        '1' => '1 - Single',
                                        '2' => '2 - Married',
                                        '3' => '3 - Living-in',
                                        '4' => '4 - Widowed',
                                        '5' => '5 - Separated',
                                        '6' => '6 - Divorced',
                                        '7' => '7 - Unknown',
                                    ]),
                                    
                                    Select::make('q9')
                                    ->required()
                                    ->reactive()
                                    ->label('Q9. Religion')
                                    ->options([
                                        '1' => '1 - Roman Catholic',
                                        '2' => '2 - Protestant',
                                        '3' => '3 - INC',
                                        '4' => '4 - Aglipay',
                                        '5' => '5 - Islam',
                                        '6' => '6 - Hinduism',
                                        '7' => '7 - Jehova\'s-Witnesses',
                                        '8' => '8 - Adventist',
                                        '9' => '9 - Christian',
                                        '10' => '10 - Other Christian',
                                        '11' => '11 - Others'
                                    ]),

                                    TextInput::make('q9_others')
                                    ->required()
                                    ->label('Q9.1 Specify Religion')
                                    ->hidden(fn (Closure $get) => $get('q9') != '22'),
                                    
                                    Select::make('q10')
                                    ->required()
                                    ->label('Q10. Ethnicity')
                                    ->options([
                                        '1' => '1 - Tagalog',
                                        '2' => '2 - Cebuano',
                                        '3' => '3 - Ilocano',
                                        '4' => '4 - Bicolano',
                                        '5' => '5 - Waray',
                                        '6' => '6 - Kapampangan',
                                        '7' => '7 - Manobo',
                                        '8' => '8 - Palaweno',
                                        '9' => '9 - Davaoeno',
                                        '10' => '10 - Ibaloi',
                                        '11' => '11 - Ibanag',
                                        '12' => '12 - Igorot',
                                        '13' => '13 - Pangasinense',
                                        '14' => '14 - Badjao',
                                    ]),

                                    Select::make('q11')
                                    ->required()
                                    ->label('Q11. Highest Level of Education')
                                    ->options([
                                        '0' => '0 - No Education',
                                        '1' => '1 - Pre-school',
                                        '2' => '2 - Elementary level',
                                        '3' => '3 - Elementary graduate',
                                        '4' => '4 - Highschool level',
                                        '5' => '5 - Highschool graduate',
                                        '6' => '6 - Junior HS level',
                                        '7' => '7 - Junior HS graduate',
                                        '8' => '8 - Senior HS level',
                                        '9' => '9 - Senior HS graduate',
                                        '10' => '10 - Vocational/Tech',
                                        '11' => '11 - College level',
                                        '12' => '12 - College graduate',
                                        '13' => '13 - Post-graduate',
                                    ]),

                                    Select::make('q12')
                                    ->required()
                                    ->reactive()
                                    ->label('Q12. Currently Enrolled')
                                    ->options([
                                        '1' => '1 - Yes Public',
                                        '2' => '2 - Yes Private',
                                        '3' => '3 - No',
                                    ]),

                                    Select::make('q13')
                                    ->required()
                                    ->label('Q13. Type of School')
                                    ->hidden(fn (Closure $get) => $get('q12') == '3')
                                    ->disabled(fn (Closure $get) => $get('q12') == null)
                                    ->options([
                                        '1' => '1 - Elementary',
                                        '2' => '2 - Junior High School',
                                        '3' => '3 - Senior High School',
                                        '4' => '4 - Vocational/Technical',
                                        '5' => '5 - College/University',
                                    ]),
                                    
                                    TextInput::make('q14')
                                    ->required()
                                    ->label('Q14. Place of School')
                                    ->hidden(fn (Closure $get) => $get('q12') == '3')
                                    ->disabled(fn (Closure $get) => $get('q12') == null),
                                ])
                                ->columns(2)
                                ->collapsed(),
                            // Q15 TO Q24
                            Section::make('II. Q15 to Q24')
                                ->schema([
                                    TextInput::make('q15')
                                    ->required()
                                    ->numeric()->minvalue(1)
                                    ->label('Q15. Monthly Income'),

                                    Select::make('q16')
                                    ->required()
                                    ->label('Q16. Source of Income')
                                    ->options([
                                        '1' => '1 - Employment',
                                        '2' => '2 - Business',
                                        '3' => '3 - Remittance',
                                        '4' => '4 - Investments',
                                        '5' => '5 - Others',
                                        '6' => '6 - Unemployed'
                                    ]),

                                    Select::make('q17')
                                    ->required()
                                    ->label('Q17. Status of Work/Business')
                                    ->hidden(fn (Closure $get) => $get('q16') == '6')
                                    ->disabled(fn (Closure $get) => $get('q16') == null)
                                    ->options([
                                        '1' => '1 - Permanent Work',
                                        '2' => '2 - Casual Work',
                                        '3' => '3 - Contractual Work',
                                        '4' => '4 - Individually Owned',
                                        '5' => '5 - Business',
                                        '6' => '6 - Shared/Partnership'
                                    ]),

                                    Select::make('q18')
                                    ->required()
                                    ->label('Q18. FP Method')
                                    ->options([
                                        '0' => '0 - None',
                                        '1' => '1 - Female Sterilization/Ligation',
                                        '2' => '2 - Male Sterilization/Vasectomy',
                                        '3' => '3 - IUD',
                                        '4' => '4 - Injectibles',
                                        '5' => '5 - Implants',
                                        '6' => '6 - Pill',
                                        '7' => '7 - Condom',
                                        '8' => '8 - Modern Natural FP',
                                        '9' => '9 - Lactational Amenorrhea Method (LAM)',
                                    ]),

                                    Select::make('q19')
                                    ->required()
                                    ->label('Q19. Intention to use FP')
                                    ->options([
                                        '1' => '1 - Yes',
                                        '2' => '2 - No'
                                    ]),

                                    Select::make('q20')
                                    ->required()
                                    ->label('Q20. Disability')
                                    ->options([
                                        '1' => '1 - Yes',
                                        '2' => '2 - No'
                                    ]),

                                    Select::make('q21')
                                    ->required()
                                    ->label('Q21. Solo Parent')
                                    ->options([
                                        '1' => '1 - Registered Solo Parent',
                                        '2' => '2 - Unregistered Solo Parent',
                                        '3' => '3 - Non-solo Parent',
                                    ]),

                                    Select::make('q22')
                                    ->required()
                                    ->label('Q22. Registered Senior Citizen')
                                    ->options([
                                        '1' => '1 - Yes',
                                        '2' => '2 - No'
                                    ]),

                                    TextInput::make('q23')
                                    ->maxlength(500)
                                    ->label('Q23. Skills Development Training'),

                                    TextInput::make('q24')
                                    ->maxlength(500)
                                    ->label('Q24. Skills'),

                                ])
                                ->columns(2)
                                ->collapsed()
                                ->hidden(fn (Closure $get) => $get('q12') == null),

                            
                        ])
                        ->reactive()
                        ->minItems(fn (Closure $get) => $get('household_members_total'))
                        ->collapsed()
                        ->defaultItems(1)
                        ->maxItems(fn (Closure $get) => $get('household_members_total')),               
                ])
                ->hidden(fn (Closure $get) => $get('household_members_total') == null),

                
                // HOUSEHOLD QUESTIONS
                Section::make('Household Questions')->schema([
                    
                    Select::make('q25')
                    ->required()
                    ->label('Q25. Do you own or amortize this housing unit occupied by your household or do you rent it?')
                    ->options([
                        '1' => '1 - Own',
                        '2' => '2 - Rent'
                    ]),

                    Select::make('q26')
                    ->required()
                    ->label('Q26. Do you own or amortize this lot occupied by your household or do you rent it?')
                    ->options([
                        '1' => '1 - Own',
                        '2' => '2 - Rent'
                    ]),

                    Select::make('q27')
                    ->required()
                    ->label('Q27. What type of fuel does this household use for lighting?')
                    ->options([
                        '1' => '1 - Utilizes Electricity',
                        '2' => '2 - Kerosene',
                        '3' => '3 - Others',
                    ]),

                    Select::make('q28')
                    ->required()
                    ->label('Q28. What kind of fuel does this household use most of the time for cooking?')
                    ->options([
                        '1' => '1 - Utilizes Electricity',
                        '2' => '2 - Kerosene',
                        '3' => '3 - Charcoal',
                        '4' => '4 - Biogas',
                        '5' => '5 - LPG',
                        '6' => '6 - Others',
                    ]),

                    Select::make('q29')
                    ->required()
                    ->label('Q29. What is the household\'s main source of drinking water?')
                    ->options([
                        '1' => '1 - Tap Water',
                        '2' => '2 - Well/Borehole with Handpump',
                        '3' => '3 - Drinking Water Supplier',
                        '4' => 'Others',
                    ]),

                    Select::make('q30')
                    ->required()
                    ->label('Q30. Do you segregate Garbage?')
                    ->options([
                        '1' => '1 - Yes',
                        '2' => '2 - No'
                    ]),

                    Select::make('q31')
                    ->reactive()
                    ->required()
                    ->label('Q31. Type of House')
                    ->options([
                        '1' => '1 - Appartment',
                        '3' => '3 - Condo',
                        '4' => '4 - Bungalow',
                        '5' => '5 - Single Detached',
                        '6' => '6 - Single Detached Two-Story',
                        '7' => '7 - Single Attached',
                        '8' => '8 - Single Attached Two-Story',
                        '9' => '9 - Duplex',
                        '10' => '10 - Quadruplex',
                        '11' => '11 - Townhouse',
                    ]),

                    TextInput::make('encoder_name')
                    ->required()
                    ->disabled()
                    ->maxLength(255)
                    ->default($user->name),
                    
                ])
                ->collapsed()
                ->columns(3)
                ->hidden(fn (Closure $get) => $get('household_members_total') == null),
                
                FileUpload::make('signature')
                ->image()
                ->columns(1)
                ->required()
                ->panelAspectRatio('2:1')
                ->panelLayout('integrated')
                ->label('Signature of the Household Head or The Respondent')
                ->hidden(fn (Closure $get) => $get('q31') == null),


                // DATA PRIVACY CONSENT
                Section::make('Data Privacy Consent')->schema([
                    Checkbox::make('data_privacy_consent')
                    ->required()
                    ->label('Lubos kong naunawaan ang layunin ng pananaliksik at Census ng barangay. Nabasa ko at pinaliwanag sa
                    akin ang nilalaman ng kasulatan at kusang loob akong sumasangayon na makibahagi sa proyektong ito.
                    Naunawaan kong magiging kompidensiyal ang lahat ng aking kasagutan. Gayunpaman, pinahihintulutan
                    ko ang paggamit ng aking impormasyon ng barangay kalakip ng paggalang sa aking “data privacy rights”.')
                ])
                ->hidden(fn (Closure $get) => $get('q31') == null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('household_number')->searchable()->sortable()->toggleable()->icon('heroicon-s-home')->color('primary'),
                Tables\Columns\TextColumn::make('name_of_respondent')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('household_head')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('household_members_total')->sortable()->label('Total Household Members')->toggleable()->color('primary')->icon('heroicon-s-user')->iconPosition('after')->size('lg'),
                Tables\Columns\TextColumn::make('province')->sortable()->toggleable()->description(fn (PopulationRecord $record): string => Philprovince::where('provCode', '=', $record->province)->get('provDesc')[0]->provDesc),
                Tables\Columns\TextColumn::make('city_or_municipality')->sortable()->toggleable()->description(fn (PopulationRecord $record): string => Philmuni::where('citymunCode', '=', $record->city_or_municipality)->get('citymunDesc')[0]->citymunDesc),
                Tables\Columns\TextColumn::make('barangay')->sortable()->toggleable(isToggledHiddenByDefault: true)->description(fn (PopulationRecord $record): string => Philbrgy::where('brgyCode', '=', $record->barangay)->get('brgyDesc')[0]->brgyDesc),
                Tables\Columns\TextColumn::make('encoder_name')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->sortable()->toggleable(isToggledHiddenByDefault: true),
                // ImageColumn::make('signature'),
                
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->label("Export this table"),
                    ExcelExport::make('model')->fromModel()->label("Export from database model"),
                    ExcelExport::make('form')->fromForm()->label("Export all data"),

                ])
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPopulationRecords::route('/'),
            'create' => Pages\CreatePopulationRecord::route('/create'),
            'edit' => Pages\EditPopulationRecord::route('/{record}/edit'),
        ];
    }    

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if ($user->getRoleNames()->first() === 'Superadmin'){
            return parent::getEloquentQuery()->where('encoder_name', '!=', '');
        } 
        
        // Add barangay viewing
        elseif($user->getRoleNames()->first() === 'Barangay') {
            return parent::getEloquentQuery()->where('barangay', '=', strtolower($user->barangay));
        }

        elseif($user->getRoleNames()->first() === 'Enumerator') {
            return parent::getEloquentQuery()->where('encoder_name', '=', $user->name);
        }
    }
}
