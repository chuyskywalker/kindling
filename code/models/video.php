<?php

class Item_video extends Item{

    const TYPE = 'video';

    static $fields = array(
          array('id' => 'title', 'label' => 'Title', 'type' => Form::TYPE_TEXT, 'rules' => array(Form::RULE_REQUIRED => ''))
        , array('id' => 'url', 'label' => 'Video URL', 'type' => Form::TYPE_URL, 'rules' => array(Form::RULE_REQUIRED => '', Form::RULE_URL_VIDEO => ''))
        , array('id' => 'comment', 'label' => 'Comment', 'type' => Form::TYPE_LONGTEXT, 'rules' => array())
    );

}