<?php

namespace App\Imports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

class OrdersImport implements ToModel, WithStartRow
{
    private ?string $currentDepartmentCode = null;
    private ?string $currentOrderNo = null;
    private bool $isHeaderRow = false;
    private array $importedOrders = [];

    public function startRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        $col20 = $row[20] ?? null; // تاريخ الطلب
        $col25 = $row[25] ?? null; // كود الإدارة أو رقم الطلب
        $col29 = $row[29] ?? null; // مؤشر نوع الصف
        $col12 = $row[12] ?? null; // تاريخ الموافقة

        $col25 = $this->cleanValue($col25);
        $col29 = $this->cleanValue($col29);

        // 1. التعرف على كود الإدارة
        if ($col29 === 'كود الإدارة' && !empty($col25)) {
            $this->currentDepartmentCode = $this->formatNumber($col25);
            $this->isHeaderRow = false;
            return null;
        }

        // 2. التعرف على رقم الطلب
        if ($col29 === 'رقم الطلب' && !empty($col25)) {
            $this->currentOrderNo = $this->formatNumber($col25);
            $this->isHeaderRow = true;
            return null;
        }

        // 3. تخطي صف الهيدر
        if ($this->isHeaderRow) {
            $this->isHeaderRow = false;
            return null;
        }

        // 4. صف بيانات - نستخرج التواريخ
        if ($this->currentDepartmentCode && $this->currentOrderNo) {

            $orderDate = $this->parseDate($col20);
            $approvalDate = $this->parseDate($col12);

            if (!$orderDate && !$approvalDate) {
                return null;
            }

            $orderKey = $this->currentDepartmentCode . '_' . $this->currentOrderNo;

            // لو الطلب اتاستورد قبل كده، نتخطاه
            if (isset($this->importedOrders[$orderKey])) {
                return null;
            }

            // نتحقق من قاعدة البيانات
            $existingOrder = Order::where('order_no', $this->currentOrderNo)
                ->where('department_name', $this->currentDepartmentCode)
                ->first();

            if ($existingOrder) {
                if ($orderDate && !$existingOrder->order_date) {
                    $existingOrder->order_date = $orderDate;
                    $existingOrder->save();
                }
                if ($approvalDate && !$existingOrder->approval_date) {
                    $existingOrder->approval_date = $approvalDate;
                    $existingOrder->save();
                }
                $this->importedOrders[$orderKey] = true;
                return null;
            }

            $this->importedOrders[$orderKey] = true;

            return new Order([
                'department_name' => $this->currentDepartmentCode,
                'order_no'        => $this->currentOrderNo,
                'order_date'      => $orderDate,
                'approval_date'   => $approvalDate,
                'po_no'           => null,
                'po_date'         => null,
            ]);
        }

        return null;
    }

    private function cleanValue($value): ?string
    {
        if (is_null($value)) return null;
        $value = trim((string) $value);
        $value = rtrim($value, ':');
        $value = rtrim($value, ' :');
        return $value;
    }

    private function formatNumber($value): string
    {
        if (is_numeric($value)) {
            return (string) intval($value);
        }
        return (string) $value;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;

        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            if (is_numeric($value) && $value > 1) {
                return Carbon::createFromTimestamp(($value - 25569) * 86400)->format('Y-m-d');
            }

            $value = trim((string) $value);
            return Carbon::parse($value)->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }
}
