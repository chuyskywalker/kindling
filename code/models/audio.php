<?php

class Item_audio extends Item{

    const TYPE = 'audio';

    static $fields = array(
          array('id' => 'title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'url', 'type' => Form::TYPE_URL, 'rules' => array(Form::RULE_REQUIRED => '', Form::RULE_URL_AUDIO => ''))
        , array('id' => 'comment', 'type' => Form::TYPE_LONGTEXT, 'rules' => array())
    );

}