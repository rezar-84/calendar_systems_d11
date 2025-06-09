<?php

namespace Drupal\calendar_systems\Plugin\views\argument;

trait CalendarSystemsArgHandlerTrait {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $cal = _calendar_systems_factory();
    if ($cal && $cal->getCalendarName() !== 'gregorian') {
      if ($cal->parse(_calendar_systems_arg_handler_trait_translate($this->argument), $this->argFormat)) {
        $this->argument = $cal->xFormat($this->argFormat);
      }
    }
    parent::query($group_by);
  }

}
