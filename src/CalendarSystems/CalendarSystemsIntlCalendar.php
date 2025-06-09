<?php

namespace Drupal\calendar_systems\CalendarSystems;

use DateTime;
use Exception;
use IntlDateFormatter;
use IntlTimeZone;

class CalendarSystemsIntlCalendar extends CalendarSystemsPartialImplementation implements CalendarSystemsInterface {

  /**
   * php's date format modifiers differ from Intl's. This is a mapping of the
   * two.
   *
   * @var array
   */
  private static $php2intl_format_map = [
    'd' => 'dd',
    'D' => 'eee',
    'j' => 'd',
    'l' => 'eeee',
    'N' => 'ee',
    'S' => '',
    'w' => '',
    'z' => '',
    'W' => 'w',
    'm' => 'MM',
    'M' => 'MMM',
    'F' => 'MMMM',
    'n' => 'M',
    't' => '',
    'L' => '',
    'o' => 'YYYY',
    'y' => 'yy',
    'Y' => 'yyyy',
    'a' => 'a',
    'A' => 'a',
    'B' => '',
    'g' => 'h',
    'G' => 'H',
    'h' => 'hh',
    'H' => 'HH',
    'i' => 'mm',
    's' => 'ss',
    'u' => 'SSSSSS',
    'e' => 'VV',
    'I' => '',
    'O' => 'xx',
    'P' => 'xxx',
    'T' => 'v',
    'Z' => '',
    'c' => '',
    'r' => '',
    'U' => '',
    ' ' => ' ',
    '-' => '-',
    '.' => '.',
    ':' => ':',
  ];

  /**
   * Some format modifiers are not supported in intl. They are simply removed.
   *
   * @var array
   */
  private static $remove_pattern = '/[bfkpqvxCEJKQRVX]/';

  protected $intlFormatter;

  protected $locale;

  public function __construct($tz, $calendar, $lang_code) {
    parent::__construct($tz, $calendar, $lang_code);
    $this->locale = $lang_code . '@calendar=' . $calendar;
    $this->intlFormatter = self::intl($this->timezone, $this->locale);
  }

  private static function intl($tz, $locale) {
    $none = IntlDateFormatter::NONE;
    $tz = IntlTimeZone::fromDateTimeZone($tz);
    $cal = IntlDateFormatter::TRADITIONAL;
    return new IntlDateFormatter($locale, $none, $none, $tz, $cal);
  }

  public function setDateLocale($y, $m, $d) {
    $y = intval($y);
    $m = intval($m);
    $d = intval($d);
    list($gy, $gm, $gd) = $this->toGregorian($this->intlFormatter, $this->timezone, $y, $m, $d);
    parent::xSetDate($gy, $gm, $gd);
    return $this;
  }

  private static function toGregorian(IntlDateFormatter $fmt, $tz, $y, $m, $d) {
    $fmt->setPattern(static::format2pattern('n/j/Y H:i:s'));
    $fmt->setLenient(TRUE);
    $ts = $fmt->parse($m . '/' . $d . '/' . $y . ' 12:00:00');
    $d = new DateTime('@' . $ts, $tz);
    return [$d->format('Y'), $d->format('n'), $d->format('j')];
  }

  private static function format2pattern($format) {
    $rep = preg_replace(self::$remove_pattern, '', $format);
    $pat = strtr($rep, self::$php2intl_format_map);
    return $pat;
  }

  public function copy() {
    return new CalendarSystemsIntlCalendar($this->timezone, $this->calendar, $this->langCode);
  }

  public function parse($value, $format) {
    $pat = static::format2pattern($format);
    $this->intlFormatter->setPattern($pat);
    try {
      $timestamp = $this->intlFormatter->parse($value);
      $timestamp = intval($timestamp);
      $this->setTimestamp($timestamp);
      if ($this->format($format) !== $value) {
        return FALSE;
      }
    }
    catch (Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

  public function format($format) {
    $this->intlFormatter->setPattern(static::format2pattern($format));
    return $this->formatHook($format, $this->intlFormatter->format($this->getTimestamp()));
  }

  protected function formatHook($format, $value) {
    return $value;
  }

  function getBaseYear() {
    return 2018;
  }

}
