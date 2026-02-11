<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ImportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Carbon\Carbon;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'الطلبات';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('department_name')
                    ->label('الإدارة الطالبة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order_no')
                    ->label('رقم الطلب')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval_date')
                    ->label('تاريخ الموافقة')
                    ->date(),

                Tables\Columns\TextColumn::make('po_no')
                    ->label('رقم أمر التوريد')
                    ->placeholder('لم يصدر'),

                Tables\Columns\TextColumn::make('po_date')
                    ->label('تاريخ أمر التوريد')
                    ->date()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('lead_time')
                    ->label('فترة التنفيذ')
                    ->getStateUsing(function ($record) {
                        if (!$record->order_date || !$record->po_date) return '—';
                        try {
                            $days = Carbon::parse($record->order_date)->diffInDays(Carbon::parse($record->po_date));
                            return $days . ' يوم';
                        } catch (\Exception $e) { return '—'; }
                    })
                    ->color(fn ($state) => (int)$state > 30 ? 'danger' : 'success'),
            ])
            ->headerActions([
                // 1. زر رفع الطلبات الأساسية
                Action::make('importOrders')
                    ->label('رفع الطلبات')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('attachment')
                            ->label('اختر ملف الإكسيل الأساسي')
                            ->disk('public')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['attachment']);
                        // نستخدم OrdersImport الموجود في app/Imports
                        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\OrdersImport, $file);

                        \Filament\Notifications\Notification::make()
                            ->title('تم استيراد الطلبات بنجاح')
                            ->success()
                            ->send();
                    }),

                // 2. زر "أمر التوريد" (تم تصحيح الاسم هنا)
                Action::make('importPO')
                    ->label('أمر التوريد')
                    ->icon('heroicon-o-plus-circle')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('attachment')
                            ->label('اختر ملف الإكسيل (أمر التوريد)')
                            ->disk('public')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $file = storage_path('app/public/' . $data['attachment']);
                        // تصحيح: POUpdateImporter (بإضافة حرف r) ليتطابق مع صورتك
                        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\POUpdateImporter, $file);

                        \Filament\Notifications\Notification::make()
                            ->title('تم تحديث بيانات التوريد بنجاح')
                            ->success()
                            ->send();
                    }),

                // 3. زر مسح البيانات
                Action::make('emptyTable')
                    ->label('مسح الكل')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn() => Order::truncate()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrders::route('/')];
    }
}