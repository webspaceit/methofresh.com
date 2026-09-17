<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_OrderItem extends wsit_Model
{
    protected string $table = 'order_items';

    public function create(array $data): int
    {
        return (int)$this->insertRecord($this->table, $data);
    }

    public function createMany(int $orderId, array $items): void
    {
        $this->beginTransaction();
        try {
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $this->insertRecord($this->table, $item);
            }
            $this->commit();
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }
}