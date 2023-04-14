<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
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
use Closure;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserResource extends Resource
{   


    protected static function getNavigationBadge(): ?string
    {
        $user = auth()->user();    
        if ($user->hasRole('Superadmin')){
            return User::count();
        } else if ($user->hasRole('LGU')){
            return User::where('province', '=', auth()->user()->province)->where('city_or_municipality', '=', auth()->user()->city_or_municipality)->role('Barangay')->count();
        } else if ($user->hasRole('Barangay')){
            return User::where('province', '=', auth()->user()->province)->where('city_or_municipality', '=', auth()->user()->city_or_municipality)->where('barangay', '=', auth()->user()->barangay)->role('Barangay')->count();
        }
    }

    protected function getTableFilters(): array
    {
        return [
            // ...
        ];
    }

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'email',
            'created_by',
            'contact',
            'province',
            'city_or_municipality',
            'barangay',
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {

        $user = auth()->user();

        return $form
            ->schema([
                
                Section::make('Basic User Information')->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignorable: fn ($record) => $record),

                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignorable: fn ($record) => $record),

                    TextInput::make('contact')
                        ->numeric()                        
                        ->required()
                        ->unique(ignorable: fn ($record) => $record),
                    TextInput::make('password')
                        ->password()
                        ->maxLength(255)
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->required(fn (string $context): bool => $context === 'create'),
                    Select::make('roles')
                        ->preload()
                        ->reactive()
                        ->required()
                        ->multiple(1)
                        ->relationship('roles', 'name')
                        ->options(function() use ($user) {
                            if ($user->getRoleNames()->first() === 'Superadmin'){
                                return Role::all()->pluck('name', 'id');
                            } elseif ($user->getRoleNames()->first() === 'LGU') {
                                return Role::where([['name', '!=', 'Superadmin'], ['name', '!=', 'LGU'] , ['name', '!=', 'Enumerator']])->pluck('name', 'id');
                            } elseif ($user->getRoleNames()->first() === 'Barangay') {
                                return Role::where([['name', '!=', 'Superadmin'], ['name', '!=', 'LGU'], ['name', '!=', 'Barangay']])->pluck('name', 'id');
                            } 
                        }),

                    Select::make('province')
                        ->reactive()
                        ->label('Province Name')
                        ->hidden(fn (Closure $get) => $get('roles') == null)
                        ->disabled(fn (Closure $get) => $get('roles') == null)
                        ->required(fn (Closure $get) => $get('roles')[0] != 1)
                        ->options(function() use ($user) {
                            if ($user->getRoleNames()->first() === 'Barangay'){
                                return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                            } else if ($user->getRoleNames()->first() === 'LGU'){
                                return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                            } else {
                                return Philprovince::all()->pluck('provDesc', 'provCode');
                            }
                        })
                        ->default(function() use ($user) {
                            if ($user->getRoleNames()->first() === 'Barangay'){
                                return Philprovince::where('provCode', '=', $user->province)->first()->provCode; 
                            } else if ($user->getRoleNames()->first() === 'LGU'){
                                return Philprovince::where('provCode', '=', $user->province)->first()->provCode; 
                            } else {
                                return false;
                            }
                        }),

                    Select::make('city_or_municipality')
                        ->reactive()
                        ->label('City/Municipality')
                        ->hidden(fn (Closure $get) => $get('roles') == null)
                        ->disabled(fn (Closure $get) => ($get('province') == null))
                        ->required(fn (Closure $get) => $get('roles')[0] != 1)
                        ->options(function(callable $get) use ($user) {
                            if ($user->getRoleNames()->first() === 'Barangay'){
                                return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                            } else if ($user->getRoleNames()->first() === 'LGU'){
                                return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                            } else {
                                return Philmuni::where('provCode', '=', $get('province'))->pluck('citymunDesc', 'citymunCode');
                            }
                        })
                        ->default(function() use ($user) {
                            if ($user->getRoleNames()->first() === 'Barangay'){
                                return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->first()->citymunCode; 
                            } else if ($user->getRoleNames()->first() === 'LGU'){
                                return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->first()->citymunCode; 
                            } else {
                                return false;
                            }
                        }),

                    // Additional Information
                    Select::make('permissions')
                        ->preload()
                        ->multiple()
                        ->relationship('permissions', 'name'),
                    TextInput::make('created_by')
                        ->disabled()
                        ->default(auth()->user()->name)
                ])
                ->columns(2),

                // FOR LGUs ONLY
                Section::make('LGU Information')->schema([
                    
                    TextInput::make('brgy_count')
                        ->numeric()->minValue(1)
                        ->label('Number of Brgy')
                        ->hidden(fn (Closure $get) => $get('roles') == null)
                        ->disabled(fn (Closure $get) => $get('roles')[0] != 2)
                        ->required(fn (Closure $get) => $get('roles')[0] == 2),

                    TextInput::make('lot_area')
                        ->numeric()->minValue(1)
                        ->label('Lot Area in sqm.')
                        ->hidden(fn (Closure $get) => $get('roles') == null)
                        ->disabled(fn (Closure $get) => $get('roles')[0] != 2)
                        ->required(fn (Closure $get) => $get('roles')[0] == 2),

                ])
                ->columns(2)
                ->collapsible()
                ->hidden(fn (Closure $get) => $get('roles') == null)
                ->description("(To be filled for LGU account type only. This form will be disabled otherwise.)"),

                // FOR BARANGAYs ONLY
                Section::make('Barangay Information')->schema([

                    Select::make('barangay')
                        ->label('Barangay Name')
                        ->unique(ignorable: fn ($record) => $record)
                        ->hidden(fn (Closure $get) => $get('city_or_municipality') == null)
                        ->disabled(fn (Closure $get) => $get('roles')[0] != 3)
                        ->required(fn (Closure $get) => $get('roles')[0] == 3)
                        ->options(function(callable $get) {
                            return Philbrgy::where('citymunCode', '=', $get('city_or_municipality'))->pluck('brgyDesc', 'brgyCode');
                        }),

                    
                    TextInput::make('purok_count')
                        ->numeric()->minValue(1)
                        ->label('Number of Purok')
                        ->hidden(fn (Closure $get) => $get('roles') == null)
                        ->disabled(fn (Closure $get) => $get('roles')[0] != 3)
                        ->required(fn (Closure $get) => $get('roles')[0] == 3),

                ])
                ->columns(2)
                ->collapsible()
                ->hidden(fn (Closure $get) => $get('roles') == null)
                ->description("(To be filled for BARANGAY account type only. This form will be disabled otherwise.)"),

                // FOR ENUMERATORS ONLY
                Section::make('Enumerator Information')->schema([
                    
                
                    Select::make('barangay')
                        ->label('Barangay Name')
                        ->disabled(fn (Closure $get) => $get('roles')[0] != 4)
                        ->required(fn (Closure $get) => $get('roles')[0] == 4)
                        ->options(function(callable $get) use ($user) {
                            if ($user->getRoleNames()->first() === 'Barangay'){
                                return Philbrgy::where('brgyCode', '=', $user->barangay)->pluck('brgyDesc', 'brgyCode'); 
                            } else {
                                return Philbrgy::where('citymunCode', '=', $get('city_or_municipality'))->pluck('brgyDesc', 'brgyCode');
                            }
                        }),
                    
                    TextInput::make('household_quota')
                        ->numeric()->minValue(1)->maxValue(5000)
                        ->label('Number of Allocated Residences')
                        ->hidden(fn (Closure $get) => $get('roles') == null)
                        ->disabled(fn (Closure $get) => $get('roles')[0] != 4)
                        ->required(fn (Closure $get) => $get('roles')[0] == 4),
                    
                    
                ])
                ->columns(2)
                ->collapsible()
                ->hidden(fn (Closure $get) => $get('roles') == null)
                ->description("(To be filled for BARANGAY account type only. This form will be disabled otherwise.)")

                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([ 
                
                TextColumn::make('name')->searchable()->sortable()->toggleable(),
                TextColumn::make('email')->searchable()->sortable()->toggleable()->default('Undefined'),
                TextColumn::make('roles.name')
                    ->searchable()->sortable()->toggleable(),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime(),
                TextColumn::make('created_by')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('province')->searchable()->sortable()->default('Undefined')->toggleable(isToggledHiddenByDefault: true)->description(function(User $record){
                    if ($record->province){
                        return Philprovince::where('provCode', '=', $record->province)->get('provDesc')[0]->provDesc;
                    } else {
                        return null;
                    }
                    
                }),
                TextColumn::make('city_or_municipality')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true)->default('Undefined')->description(function(User $record){
                    if ($record->city_or_municipality){
                        return Philmuni::where('citymunCode', '=', $record->city_or_municipality)->get('citymunDesc')[0]->citymunDesc;
                    } else {
                        return null;
                    }
                    
                }),
                TextColumn::make('barangay')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true)->default('Undefined')->description(function(User $record){
                    if ($record->barangay){
                        return Philbrgy::where('brgyCode', '=', $record->barangay)->get('brgyDesc')[0]->brgyDesc;
                    } else {
                        return null;
                    }
                    
                }),
                TextColumn::make('created_at')->default('Undefined')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),

            ])
            
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if ($user->getRoleNames()->first() === 'Superadmin'){
            return parent::getEloquentQuery()->where('name', '!=', '');
        } elseif($user->getRoleNames()->first() === 'LGU') {
            return parent::getEloquentQuery()->where('city_or_municipality', '=', $user->city_or_municipality)
                ->whereHas('roles', function (Builder $query) {
                $query->where('name', '!=', 'Superadmin')
                    ->where('name', '!=', 'LGU')
                    ->where('name', '!=', 'Enumerator');
            });
        } elseif($user->getRoleNames()->first() === 'Barangay') {
            return parent::getEloquentQuery()->where('city_or_municipality', '=', $user->city_or_municipality)->where('barangay', '=', $user->barangay)
                ->whereHas('roles', function (Builder $query) {
                $query->where('name', '!=', 'Superadmin')
                    ->where('name', '!=', 'LGU')
                    ->where('name', '!=', 'Barangay');
            });
        }
    }
}
