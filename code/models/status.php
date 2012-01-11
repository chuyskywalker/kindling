<?php

class Item_status extends Item{

    const TYPE = 'status';

    static $fields = array(
          array('id' => 'title', 'label' => 'Status', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
    );

}