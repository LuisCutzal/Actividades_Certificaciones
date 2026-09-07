<?php

namespace Application\Model;

class StudentAttendance
{
    public ?int $id = null;
    public int $carnet;
    public int $id_activity;
    public int $id_attendance_list;
    public string $attended_at;

    public function exchangeArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->carnet = $data['carnet'];
        $this->id_activity = $data['id_activity'];
        $this->id_attendance_list = $data['id_attendance_list'];
        $this->attended_at = $data['attended_at'];
    }
}
