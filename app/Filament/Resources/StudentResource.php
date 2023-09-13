<?php

namespace App\Filament\Resources;

use App\Events\PromoteStudent;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Certificate;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\GlobalSearch\Actions\Action;
use Filament\Notifications\Collection;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'name';

   

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
             Forms\Components\Section::make('Personal info')
             ->description('Add student personal information')
             ->collapsible()
             
             ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('student_id')
                            ->required(),
                        Forms\Components\TextInput::make('address_1'),
                        Forms\Components\TextInput::make('address_2'),
                        Forms\Components\Select::make('standard_id')
                            ->required()
                            ->relationship(relationshipName: 'standard',
                                titleColumnName: 'name'),
                            ]),
               Forms\Components\Section::make('Medical information')
               ->description('Add medical information of the student')
               ->collapsible()
               ->collapsed()
               ->schema([
                        Forms\Components\Repeater::make('vitals')
                            ->schema([
                                Forms\Components\Select::make('name')
                                    ->options(config('sm_config.vitals'))
                                    ->required(),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->maxLength(255),
               ])
                 ->columns(2)
                            ]),

            Forms\Components\Section::make('Certificates')
               ->description('Add student certificate information')

               ->collapsible()   
               ->schema([
                        Forms\Components\Repeater::make('certificates')
                        ->relationship()
                            ->schema([       
                                Forms\Components\Select::make('certificate_id')
                                ->options(Certificate::all()->pluck('name', key:'id'))
                                ->searchable()
                                ->required(),
                                Forms\Components\TextInput::make('description')
                                
               ])
                 ->columns(2)
                            ]),   
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(name: 'name')->searchable(),
                Tables\Columns\TextColumn::make(name: 'standard.name')->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make(name: 'Start')
                    ->query(fn(Builder $query): Builder => $query->where(column: 'standard_id', operator: 1)),
                Tables\Filters\SelectFilter::make(name: 'standard_id')
                    ->options([
                        1 => 'Standard 1',
                        5 => 'Standard 5',
                        9 => 'Standard 9',

                    ])
                    ->label(label: 'Select the standard'),
                Tables\Filters\SelectFilter::make(name: 'All standard')
                    ->relationship(
                        relationshipName: 'standard',
                        titleColumnName: 'name'
                    )
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\EditAction::make('Promote')
                        ->action(function (Student $record) {
                            $record->standard_id = $record->standard_id + 1;
                            $record->save();
                        })
                        ->color('success')
                        ->requiresConfirmation(),

                    Tables\Actions\EditAction::make('Demote')
                        ->action(function (Student $record) {
                            if ($record->standard_id > 1) {
                                $record->standard_id = $record->standard_id - 1;
                                $record->save();
                            }

                        })
                        ->color('danger')
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('Promote All')
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            event(new PromoteStudent($record));
                        });
                })
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\GuardiansRelationManager::class
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }    
    
 public static function getGlobalSearchResultDetails(Model $record): array
  {
        return [
            'Name' => $record->name,
            'Standard' => $record->standard->name,
        ];
  }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('Edit')
                ->iconButton()
                ->icon('heroicon-s-pencil')
                ->url(static::getUrl('edit', ['record' => $record]))
        ];
    }

}
