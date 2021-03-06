<?php
/**
 * Created by VeHo.
 * Year: 2022-01-04
 */

namespace Repository;

use App\Models\MaintenanceSchedule;
use App\Repositories\Contracts\MaintenanceScheduleRepositoryInterface;
use Repository\BaseRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class MaintenanceScheduleRepository extends BaseRepository implements MaintenanceScheduleRepositoryInterface
{

    private $vehicleReposiroty;
    private $maintenanceCost;
     public function __construct(Application $app)
     {
        parent::__construct($app);
        $this->vehicleReposiroty = new VehicleRepository($app);
        $this->maintenanceCost = new MaintenanceCostRepository($app);
     }

    /**
       * Instantiate model
       *
       * @param MaintenanceSchedule $model
       */

    public function model()
    {
        return MaintenanceSchedule::class;
    }

    public function loadSchedule(int $year, int $department, string $numberOfPlate = null) {
        $startAccountingYear = $year . "-04-01";
        $endAccountingYear = ($year + 1) . "-03-31";
        $vehicles = $this->vehicleReposiroty->model->where('department_id', $department)
        ->with([
            'plate_history' => function ($query) {
                $query->orderBy('date', 'DESC')->select(['*']);
            }
        ]);
        if ($numberOfPlate) {
            $vehicles = $vehicles->whereHas('plate_history', function ($query) use ($numberOfPlate) {
                $query->where('no_number_plate', $numberOfPlate);
            });
        }
        $vehicles = $vehicles->get(['*'])->keyBy('id')->toArray();
        $vehiclesIds = [];
        foreach ($vehicles as $key => $vehicle) {
            $vehiclesIds[] = $vehicle['id'];
        }
        $maintenanceCost = $this->maintenanceCost->model->whereBetween('scheduled_date', [$startAccountingYear, $endAccountingYear])
        ->whereIn('type', [1, 2])
        ->whereIn('vehicle_id',  $vehiclesIds)
        ->get(['*']);
        $result = [];
        foreach ($maintenanceCost as $key => $cost) {
            $numberPlate = $vehicles[$cost->vehicle_id]['plate_history'][0]['no_number_plate'];
            if (!isset($result[$numberPlate])) {
                $result[$numberPlate] = [];
            }
            $result[$numberPlate][] = [
                "color" => $this->checkColor($vehicles[$cost->vehicle_id]['inspection_expiration_date'], $cost->scheduled_date),
                "date" => $cost->scheduled_date,
                "expiration_date" => $vehicles[$cost->vehicle_id]['inspection_expiration_date'],
                "first_register" => $vehicles[$cost->vehicle_id]['first_registration'],
                "no_number_plate" => $numberPlate,
                "result" => $cost->maintained_date,
                "result_remark" => "",
                "schedule_remark" => "",
                "vehicle_id" => $cost->vehicle_id,
                "department_id" => $vehicles[$cost->vehicle_id]['department_id']
            ];
        }
        return $result;
    }

    public function finalSchedule(int $year, int $department, string $numberOfPlate = null): array
    {
        return $this->logicOfMaintanenceSchedule($year, $department, $numberOfPlate);
    }

    private function logicOfMaintanenceSchedule(int $year, int $department, string $numberOfPlate = null): array {

        if ($vehicle = $this->vehicleReposiroty->findByNumberOfPlate($numberOfPlate)) {
            $vehiclesDatas = $this->vehicleReposiroty->vehiclesDatas($year, $department, $vehicle->id);
        } else $vehiclesDatas = $this->vehicleReposiroty->vehiclesDatas($year, $department);

        $yearShift = $this->createYearShift($year);
        foreach ($vehiclesDatas as $key => $vehicle) {
            $plates = $vehicle->plate_history->toArray();
            $this->scheduleEachThreeMonth($vehicle->id, $plates[0]['no_number_plate'], $vehicle->first_registration, $vehicle->inspection_expiration_date, $yearShift);
            $this->scheduleEachDay($yearShift, $year);
        }
        return $yearShift;
    }

    private function scheduleEachDay(array &$yearShift, int $year): void {
        foreach ($yearShift as $month => &$monthShift) {
            $nextYear = $year + 1;
            if ($month <= 3) {
                $yearMonth = $nextYear . "-" . $month;
            } else if ($month >= 4) {
                $yearMonth = $year . "-" . $month;
            }
            $dayInMonth = Carbon::parse($yearMonth)->daysInMonth;
            $day = 0;
            foreach ($monthShift as $key => &$vehicleSchedule) {
                $day ++;
                $vehicleSchedule['date'] = $yearMonth . "-" . $day;
                if ($day == $dayInMonth) $day = 0;
            }
        }
    }

    private function scheduleEachThreeMonth(int $vehicle_id, string $vehicle_plate, string $firstOfRegister, string $expirationDate, array &$yearShift) {
        $count = 1;
        $month = (int)date('m', strtotime($firstOfRegister));
        $monthFirstOfRegister = $month;
        while ($count <= 4) {
            $yearShift[$month][] = [
                "vehicle_id" => $vehicle_id,
                "no_number_plate" => $vehicle_plate,
                "first_register" => $firstOfRegister,
                "expiration_date" => $expirationDate,
                "date" => null,
                "result" => null,
                "color" => ($month == $monthFirstOfRegister) ? 2 : 1, // 0 gray 1 blue 2 yeallow,
                "schedule_remark" => "",
                "result_remark" => ""
            ];
            if ($month + 3 <= 12) $month += 3;
            else if ($month + 3 > 12) $month = ($month + 3) - 12;
            $count += 1;
        }
    }

    private function createYearShift(int $year): array {
        $result = [
            4 => [],
            5 => [],
            6 => [],
            7 => [],
            8 => [],
            9 => [],
            10 => [],
            11 => [],
            12 => [],
            1 => [],
            2 => [],
            3 => [],
        ];
        // foreach ($result as $key => &$month) {
        //     $yearMonth = $year . "-" . $key;
        //     $dayInMonth = Carbon::parse($yearMonth)->daysInMonth;
        //     for ($i = 1; $i <= $dayInMonth; $i++) {
        //         $month[$i] = [];
        //     }
        // }
        return $result;
    }

    private function checkColor($firstOfRegister, $scheduled_date) {
        $monthFirstOfRegister = date('m', strtotime($firstOfRegister));
        $monthScheduledDate = date('m', strtotime($scheduled_date));
        if ($monthFirstOfRegister == $monthScheduledDate) {
            return 2;
        }
        return 1;
    }
}

// logic
// select to??n b??? vehicle data, order by ID asc => v?? l???ch ???????c ph??n b??? theo th??ng v?? id nh??? h??n s??? l???y ng??y nh??? h??n.
// trong 1 th??ng, 1 s??? khung(id) nh??ng 2 bi???n s??? => ??u ti??n bi???n s??? m???i nh???t.(khi thay ?????i bi???n s???, s??? ph???i hi???n s???p x???p v??o array nh?? 1 xe m???i.) // skip
// t???o schedule result theo n??m.(t??? 1 -> 12 c??ng n??m ho???c 4 to 3 n??m sau) // done
// trong schedule result ph??n theo th??ng, trong th??ng c?? ng??y, trong ng??y c?? th??ng tin c??c xe c???n maintanence //done
// t??nh to??n d???a tr??n s??? l?????ng xe c???n maintanence t???ng ng??y, v?? d??? ng??y 1 c?? 2 xe c???n maintanence,
// c??c ng??y c??n l???i c?? 01 xe c???n maintanence -> xe ti???p theo s??? ?????y v??o ng??y 02, trong c??ng th??ng ????,
// trong qu?? tr??nh t???o l???ch, t??nh to??n ?????ng th???i 3 m??u gray, blue, yellow cho t???ng th???i ??i???m trong n??m
// d???a theo ng??y hi???n t???i Carbon::now ????? t??nh gray, end of year ????? t??nh yeallow, c??n l???i l?? blue.
// sau khi ho??n t???t schedule, c??n c??? v??o schedule ????? query maintanence cost v?? ti???n h??nh add maintanence cost.
//
