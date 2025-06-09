<?php

namespace Drupal\calendar_systems\CalendarSystems;

use Drupal\Core\StringTranslation\TranslatableMarkup;

final class CalendarSystemsPersianIntlCalendar extends CalendarSystemsIntlCalendar {

  function validate(array $arr): bool|TranslatableMarkup|null {
    if ((empty($arr['year'])) &&
      (empty($arr['month'])) &&
      (empty($arr['day']))) {
      return NULL;
    }
    $year = intval($arr['year']);
    $month = intval($arr['month']);
    $day = intval($arr['day']);
    if ($year < 0 || $year === 0) {
      return t('Year out of range');
    }
    if ($month < 0 || 12 < $month || $month === 0) {
      return t('Month out of range');
    }
    if ($day === 0 || $day < 0 || 31 < $day || $month > 6 && $day > 30 || $month === 12 && $day > 29) {
      return t('Day out of range');
    }
    return FALSE;
  }

  function copy(): CalendarSystemsPersianIntlCalendar {
    return new CalendarSystemsPersianIntlCalendar($this->timezone, $this->calendar, $this->langCode);
  }

  function getBaseYear(): int {
    return 1390;
  }

  protected function formatHook($format, $value): string {
    $characters = [
      '۰' => '0',
      '۱' => '1',
      '۲' => '2',
      '۳' => '3',
      '۴' => '4',
      '۵' => '5',
      '۶' => '6',
      '۷' => '7',
      '۸' => '8',
      '۹' => '9',
      '٠' => '0',
      '١' => '1',
      '٢' => '2',
      '٣' => '3',
      '٤' => '4',
      '٥' => '5',
      '٦' => '6',
      '٧' => '7',
      '٨' => '8',
      '٩' => '9',
    ];
    return strtr($value, $characters);
  }

}
