<?php

namespace Application\Model;

class AttendanceListStudent
{
    public ?int $id = null;
    public int $id_attendance_list;
    public int $carnet;

    public function exchangeArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->id_attendance_list = $data['id_attendance_list'];
        $this->carnet = $data['carnet'];
    }
}
