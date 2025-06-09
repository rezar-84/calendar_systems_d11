<?php

/**
 * @file
 * Fallback calendar implementation in case php-intl is not available.
 */

namespace Drupal\calendar_systems\CalendarSystems;

use DateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;

final class CalendarSystemsPoorMansGregorianCalendar extends CalendarSystemsPartialImplementation implements CalendarSystemsInterface {

  public function __construct($tz, $lang_code) {
    $lang_code = $lang_code !== 'fa' && $lang_code !== 'en' ? 'en' : $lang_code;
    parent::__construct($tz, 'gregorian', $lang_code);
  }

  public function format($format): string {
    return date_format(parent::getOrigin(), $format);
  }

  public function setDateLocale($y = 1, $m = 1, $d = 1): self {
    $this->xSetDate($y, $m, $d);
    return $this;
  }

  public function copy(): self {
    return new CalendarSystemsPoorMansGregorianCalendar($this->timezone, $this->langCode);
  }

  public function validate(array $arr): bool|TranslatableMarkup|null {
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
    if ($day === 0 || $day < 0 || 31 < $day) {
      return t('Day out of range');
    }
    return FALSE;
  }

  public function parse($value, $format): bool {
    $dt = DateTime::createFromFormat($format, $value);
    if (!$dt) {
      return FALSE;
    }
    $this->setTimestamp($dt->getTimestamp());
    return TRUE;
  }

  function getBaseYear(): int {
    return 2018;
  }

}
