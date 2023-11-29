<?php

namespace CPA\Lib\Time;

use DateTime;
use DateInterval;

class DateWeek {

    /**
     * @var DateTime
     */
    public $date_time_object;
    public $const_week_first_day = 1;

    /**
     * 
     * @param string $datetime
     * @param int $first_wday
     */
    public function __construct($datetime, $first_wday = 1) {

        $object = DateTime::createFromFormat('Y-m-d', substr($datetime, 0, 10));
        if ($first_wday <= 1) {
            $this->const_week_first_day = $first_wday;
        }
        $this->date_time_object = $object;
    }

    /**
     * 取得第幾週
     * @return int
     */
    public function GetWeek() {
        $weekday = $this->date_time_object->format('w');
        if ($weekday == $this->const_week_first_day) {
            // +1 day and get week
            $this->date_time_object->add(new DateInterval('P1D'));
            $week = $this->date_time_object->format('W');
            $this->date_time_object->sub(new DateInterval('P1D'));
        } else {
            $week = $this->date_time_object->format('W');
        }
        return $week;
    }

    /**
     * 取得目前設定的日期
     * @return Y-m-d
     */
    public function GetCurrentDate() {
        return $this->date_time_object->format('Y-m-d');
    }

    /**
     * 取得該週第一天的 datetime object
     * @return DateTime
     */
    public function GetWeekFirstDateTimeObject() {
        $datetime_object_clone = clone $this->date_time_object;
        $weekday = $datetime_object_clone->format('w');
        $diffFirstDays = ($weekday + 7 - $this->const_week_first_day) % 7;
        $tmp_firstdateobject = date_sub($datetime_object_clone, new DateInterval('P' . $diffFirstDays . 'D'));
        unset($datetime_object_clone);
        return $tmp_firstdateobject;
    }

    /**
     * 取得當周首日的年份
     * @return int
     */
    public function GetWeekLocateYear() {
        $first_datetime_object = $this->GetWeekFirstDateTimeObject();
        return $first_datetime_object->format('Y');
    }

    /**
     * 取得星期的文字標籤
     * @return string
     */
    public function GetWeekdayLabel() {
        $week_label = array('日', '一', '二', '三', '四', '五', '六');
        $weekday = $this->date_time_object->format('w');
        return $week_label[$weekday];
    }

    /**
     * 取得設定日是該週的第幾天
     * @return int
     */
    public function GetWeekday() {
        $weekday = $this->date_time_object->format('w');
        return $weekday;
    }

    /**
     * 取得該週的第一天日期
     * @return string DATE
     */
    public function GetWeekFirstDate() {
        $first_datetime_object = $this->GetWeekFirstDateTimeObject();
        return $first_datetime_object->format('Y-m-d');
    }

    /**
     * 取得該週的最後一天日期
     * @return string Date
     */
    public function GetWeekLastDate() {
        $first_datetime_object = $this->GetWeekFirstDateTimeObject();
        $first_datetime_object->add(new DateInterval('P6D'));
        return $first_datetime_object->format('Y-m-d');
    }
}
