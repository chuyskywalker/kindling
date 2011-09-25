<?php

class Item_image extends Item{

    const TYPE = 'image';

    static $fields = array(
          array('id' => 'title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'filename', 'type' => Form::TYPE_IMAGEUPLOAD, 'rules' => array(Form::RULE_REQUIRED => '', Form::RULE_IMAGE_UPLOAD => ''))
        , array('id' => 'comment', 'type' => Form::TYPE_LONGTEXT, 'rules' => array())
    );

}