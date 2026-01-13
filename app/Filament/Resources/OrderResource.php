<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Carbon\Carbon;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department_name')->label('كود الإدارة')->searchable(),
                Tables\Columns\TextColumn::make('order_no')->label('رقم الطلب')->searchable(),
                Tables\Columns\TextColumn::make('order_date')->label('تاريخ الطلب')->date()->sortable(),
                Tables\Columns\TextColumn::make('approval_date')->label('تاريخ الموافقة')->date(),
                Tables\Columns\TextColumn::make('po_no')->label('PO No.')->searchable(),
                Tables\Columns\TextColumn::make('po_date')->label('PO Date')->date(),
                Tables\Columns\TextColumn::make('lead_time')
                    ->label('مدة التوريد')
                    ->getStateUsing(function ($record) {
                        if (!$record->po_date || !$record->approval_date) return '—';
                        try {
                            $approval = \Carbon\Carbon::parse($record->approval_date);
                            $poDate = \Carbon\Carbon::parse($record->po_date);
                            // حساب الفرق بالأيام
                            return $approval->diffInDays($poDate) . ' يوم';
                        } catch (\Exception $e) {
                            return '—';
                        }
                    }),
            ])
            ->defaultSort('order_date', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()->label('تصدير إلى Excel'),
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
