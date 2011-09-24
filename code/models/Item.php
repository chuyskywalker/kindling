<?php

abstract class Item {

    const REDIS_PREFIX = 'item';
    const TYPE = false;

    public function getType() {
        return static::TYPE;
    }

    public function save($id, $values) {

    }

}