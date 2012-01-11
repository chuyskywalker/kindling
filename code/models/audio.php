<?php

class Item_audio extends Item{

    const TYPE = 'audio';

    static $fields = array(
          array('id' => 'title', 'label' => 'Title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'url', 'label' => 'MP3 URL', 'type' => Form::TYPE_URL, 'rules' => array(Form::RULE_REQUIRED => '', Form::RULE_URL_AUDIO => ''))
        , array('id' => 'comment', 'label' => 'Comment', 'type' => Form::TYPE_LONGTEXT, 'rules' => array())
    );

}