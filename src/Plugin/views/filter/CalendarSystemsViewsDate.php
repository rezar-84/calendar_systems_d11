<?php

namespace Drupal\calendar_systems\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class CalendarSystemsViewsDate extends Date {

  /**
   * @inheritDoc
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (isset($form['value'])) {
      $form['value']['#attributes']['autocomplete'] = 'off';
    }
  }

  /**
   * @inheritDoc
   */
  protected function opSimple($field) {
    if (!ctype_alnum($field)) {
      parent::opSimple($field);
      return;
    }

    $this->value['value'] = _calendar_systems_arg_handler_trait_translate($this->value['value']);

    $cal = _calendar_systems_factory();
    if (!$cal) {
      parent::opSimple($field);
      return;
    }


    $min = NULL;
    $max = NULL;

    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $v = $this->value['value'];
      $ts = strtotime($v);
      if ($ts === FALSE) {
        $this->query->addWhereExpression($this->options['group'], '1 = 2');
        return;
      }
      $cal->setTimestamp($ts);
      if (strpos($v, 'second') !== FALSE) {
        $min = $ts;
        $max = $ts;
      }
      elseif (strpos($v, 'minute') != FALSE) {
        $cal->setTime($cal->format('H'), $cal->format('i'), 0);
        $min = $cal->getTimestamp();
        $cal->setTime($cal->format('H'), $cal->format('i'), 59);
        $max = $cal->getTimestamp();
      }
      elseif (strpos($v, 'hour') != FALSE) {
        $cal->setTime($cal->format('H'), 0, 0);
        $min = $cal->getTimestamp();
        $cal->setTime($cal->format('H'), 59, 59);
        $max = $cal->getTimestamp();
      }
      elseif (strpos($v, 'day') != FALSE) {
        $cal->setTime(0, 0, 0);
        $min = $cal->getTimestamp();
        $cal->setTime(23, 59, 59);
        $max = $cal->getTimestamp();
      }
      elseif (strpos($v, 'month') != FALSE) {
        $cal->setTime(0, 0, 0);
        $cal->setDateLocale($cal->format('Y'), $cal->format('m'), 1);
        $min = $cal->getTimestamp();
        $cal->setDateLocale($cal->format('Y'), $cal->format('m'), $cal->format('t'));
        $max = $cal->getTimestamp();
      }
      elseif (strpos($v, 'year') != FALSE) {
        $cal->setTime(0, 0, 0);
        $cal->setDateLocale($cal->format('Y'), 0, 1);
        $min = $cal->getTimestamp();
        // Set month to last month, then use t.
        $cal->setDateLocale($cal->format('Y'), 12, 1);
        $cal->setDateLocale($cal->format('Y'), 12, $cal->format('t'));
        $max = $cal->getTimestamp();
      }
      elseif (strpos($v, 'week' === FALSE)) {
        $this->query->addWhereExpression($this->options['group'], '1 = 2');
        return;
      }
    }
    else {
      if (!$cal->parse($this->value['value'] . ' 00:00:00', 'Y-m-d H:i:s')) {
        $this->query->addWhereExpression($this->options['group'], '1 = 2');
        return;
      }
      $min = $cal->getTimestamp();

      if (!$cal->parse($this->value['value'] . ' 23:59:59', 'Y-m-d H:i:s')) {
        $this->query->addWhereExpression($this->options['group'], '1 = 2');
        return;
      }
      $max = $cal->getTimestamp();
    }

    $this->query->addWhereExpression($this->options['group'], '1 = 2');

    switch ($this->operator) {
      case '=':
        if ($min === NULL || $max === NULL) {
          $this->query->addWhereExpression($this->options['group'], '1 = 2');
        }
        else {
          $this->query->addWhereExpression($this->options['group'], "$field BETWEEN $min AND $max");
        }
        break;

      case '!=':
        if ($min === NULL || $max === NULL) {
          $this->query->addWhereExpression($this->options['group'], '1 = 2');
        }
        else {
          $this->query->addWhereExpression($this->options['group'], "$field < $min OR $field > $max");
        }
        break;

      case '<':
        if ($min === NULL) {
          $this->query->addWhereExpression($this->options['group'], '1 = 2');
        }
        else {
          $this->query->addWhereExpression($this->options['group'], "$field < $min");
        }
        break;

      case '<=':
        if ($min === NULL) {
          $this->query->addWhereExpression($this->options['group'], '1 = 2');
        }
        else {
          $this->query->addWhereExpression($this->options['group'], "$field <= $min");
        }
        break;

      case '>':
        if ($max === NULL) {
          $this->query->addWhereExpression($this->options['group'], '1 = 2');
        }
        else {
          $this->query->addWhereExpression($this->options['group'], "$field > $max");
        }
        break;

      case '>=':
        if ($max === NULL) {
          $this->query->addWhereExpression($this->options['group'], '1 = 2');
        }
        else {
          $this->query->addWhereExpression($this->options['group'], "$field >= $max");
        }
        break;

      default:
        $this->query->addWhereExpression($this->options['group'], '1 = 2');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    $this->value['min'] = _calendar_systems_arg_handler_trait_translate($this->value['min']);
    $this->value['max'] = _calendar_systems_arg_handler_trait_translate($this->value['max']);

    $cal = _calendar_systems_factory();
    if (!$cal) {
      parent::opBetween($field);
      return;
    }

    // if type is offset translate value and delegate handling to parent class
    if ($this->value['type'] == 'offset') {
      parent::opBetween($field);
      return;
    }

    if (!$cal->parse($this->value['min'] . ' 00:00:00', 'Y-m-d H:i:s')) {
      $this->query->addWhereExpression($this->options['group'], '1 = 2');
      return;
    }
    $a = $cal->getTimestamp();

    if (!$cal->parse($this->value['max'] . ' 23:59:59', 'Y-m-d H:i:s')) {
      $this->query->addWhereExpression($this->options['group'], '1 = 2');
      return;
    }
    $b = $cal->getTimestamp();

    $operator = strtoupper($this->operator);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

}
