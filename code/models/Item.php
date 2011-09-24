<?php

abstract class Item {

    const REDIS_PREFIX = 'item';

    abstract public function getType();

    abstract public function save();

}