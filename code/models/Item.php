<?php

abstract class Item {

    const REDIS_PREFIX = 'item';
    const REDIS_POST_INDEX= 'postindex';
    const REDIS_ALLPOSTS = 'all';
    const REDIS_LATEST_POST_ID = 'latestpostid';
    const TYPE = false;

    static $fields = array();

    private $errors = array();

    public function getType() {
        return static::TYPE;
    }

    public function getFields() {
        return static::$fields;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function delete($id) {

        $oldKey = rc::key(Item::REDIS_PREFIX, $id);
        $validId = rc::get()->exists($oldKey);
        if ($validId) {
            $allItems = rc::key(self::REDIS_POST_INDEX, self::REDIS_ALLPOSTS);
            $typeItems = rc::key(self::REDIS_POST_INDEX, $this->getType());
            rc::get()->zRem($allItems, $id);
            rc::get()->zRem($typeItems, $id);
            rc::get()->del($oldKey);
        }

    }

    public function save($id, $values) {

        if (!$this->validate($id, $values)) {
            return false;
        }

        $isStatic = isset($_POST['_is_static']) && $_POST['_is_static'] == 1;

        $allItems = rc::key(self::REDIS_POST_INDEX, self::REDIS_ALLPOSTS);
        $typeItems = rc::key(self::REDIS_POST_INDEX, $this->getType());

        $fieldVals = array();

        $slug = toAscii($values['title']);
        $fieldVals['slug'] = $slug;

        $slugIsUsed = rc::get()->zScore($allItems, $slug) > 0;

        $newKey = rc::key(Item::REDIS_PREFIX, $slug);
        $oldKey = rc::key(Item::REDIS_PREFIX, $id);

        $createdAt = time();

        if (empty($id)) {
            // create new item
            $fieldVals['createdAt'] = $createdAt;

            if ($slugIsUsed) {
                // oops, looks like that slug is already used (similarily named items)
                $maxTries = 1;
                $fslug = $slug;
                while ($maxTries < 9999) {
                    $slug = $fslug.'-'.$maxTries++;
                    if (rc::get()->zScore($allItems, $slug) == false) {
                        break;
                    }
                }
                $fieldVals['slug'] = $slug;
                $newKey = rc::key(Item::REDIS_PREFIX, $slug);
            }
            else {
                // we're trying to create new and the slug is unused, nothing special to do, just add fields later
            }

            // add item to lists by time/slug
            if (!$isStatic) {
                rc::get()->zAdd($allItems, $createdAt, $slug);
                rc::get()->zAdd($typeItems, $createdAt, $slug);
            }

        }
        else {
            // editing an item
            if(!$slugIsUsed) {
                // we're trying to edit an item, but the slug no longer matches (Needs to be updated)

                // remove old record
                rc::get()->zRem($allItems, $id);
                rc::get()->zRem($typeItems, $id);

                // get the previous items so we can preserve the created date
                $createdAt = rc::get()->hGet($oldKey, 'createdAt');
                $fieldVals['createdAt'] = $createdAt;

                // remove the old item
                rc::get()->del($oldKey);

                // add item back to lists by old-time/new-slug
                if (!$isStatic) {
                    rc::get()->zAdd($allItems, $createdAt, $slug);
                    rc::get()->zAdd($typeItems, $createdAt, $slug);
                }

            }
            else if($slugIsUsed) {
                // we're trying to edit an item, and the slug exists, we just overwrite the hash for the item with new info

                // the staticness of it may have changed, add/remove as appropriate
                if ($isStatic) {
                    rc::get()->zRem($allItems, $slug);
                    rc::get()->zRem($typeItems, $slug);
                }
                else {
                    $createdAt = rc::get()->hGet($oldKey, 'createdAt');
                    rc::get()->zAdd($allItems, $createdAt, $slug);
                    rc::get()->zAdd($typeItems, $createdAt, $slug);
                }

            }
        }

        // gather up all the fields
        foreach ($this->getFields() as $field) {
            if ($field['type'] == Form::TYPE_IMAGEUPLOAD) {
                // slight different handling here

                $hascur    = !empty($id) && isset($_POST['cur_' . $field['id']]) && !empty($_POST['cur_' . $field['id']]);
                $file_real = isset($_FILES['file_' . $field['id']]) && !empty($_FILES['file_' . $field['id']]['tmp_name']) ? $_FILES['file_' . $field['id']] : false;
                $file_url  = isset($_POST['url_' . $field['id']]) ? $_POST['url_' . $field['id']] : false;
                $filename  = false;

                if ($file_real) {
                    // all the validty checks already passed, just copy it in
                    $filename = $slug . strrchr($file_real['name'],'.');
                    move_uploaded_file($file_real['tmp_name'], UPLOADDIR.'/'.$filename);
                }
                elseif ($file_url) {
                    // save image in
                    $filename = $slug . strrchr($file_url,'.');
                    copy($file_url, UPLOADDIR.'/'.$filename);
                }
                elseif ($hascur) {
                    $filename = $_POST['cur_' . $field['id']];
                }

                if ($filename) {
                    $fieldVals[$field['id']] = $filename;
                }
            }
            elseif ($field['type'] == Form::TYPE_LONGTEXT) {
                // while this is mostly the same, we also want to preprocess the field and save the formatting selection
                $rawContent = $_POST[$field['id']];
                $format = $_POST['format_'.$field['id']];

                $renderedContent = renderContent($rawContent, $format);

                $fieldVals[$field['id']] = $rawContent;
                $fieldVals['format_'.$field['id']] = $format;
                $fieldVals[$field['id'].'Rendered'] = $renderedContent;

            }
            else {
                $fieldVals[$field['id']] = $_POST[$field['id']];
            }
        }

        $fieldVals['type'] = $this->getType();
        $fieldVals['_is_static'] = (int)$isStatic;

        // and save 'em out
        rc::get()->hMset($newKey, $fieldVals);

        return true;

    }

    /**
     * @abstract
     * @param string $id
     * @param array $fields
     * @return boolean
     */
    private function validate($id, $fields) {
        foreach ($this->getFields() as $field) {
            $fieldVal = isset($fields[$field['id']]) ? trim($fields[$field['id']]) : '';
            if (isset($field['rules']) && count($field['rules'])) {
                foreach ($field['rules'] as $rule => $operator) {
                    switch ($rule) {

                        case Form::RULE_REQUIRED:
                            // if the field is empty OR it is an image upload and has neither file nor url param, this qualifies as "missing"
                            if ($field['type'] == Form::TYPE_IMAGEUPLOAD) {
                                // TODO: Using _FILES and _POST here is cheating...
                                $hascur  = !empty($id) && isset($_POST['cur_' . $field['id']]) && !empty($_POST['cur_' . $field['id']]);
                                $hasurl  = isset($_POST['url_' . $field['id']])   && !empty($_POST['url_' . $field['id']]);
                                $hasfile = isset($_FILES['file_' . $field['id']]) && !empty($_FILES['file_' . $field['id']]['tmp_name']);
                                if (!$hascur && !$hasfile && !$hasurl) {
                                    $this->errors[] = $field['label'] . ' is missing and is required.';
                                }
                            }
                            elseif($fieldVal == '') {
                                $this->errors[] = $field['label'] . ' is missing and is required.';
                            }
                            break;

                        case Form::RULE_URL:
                            if (!preg_match(Form::REGEX_URL, $fieldVal)) {
                                $this->errors[] = $field['label'] . ' is not a valid URL.';
                            }
                            break;

                        case Form::RULE_URL_AUDIO:
                            if (!preg_match(Form::REGEX_URL_AUDIO, $fieldVal)) {
                                $this->errors[] = $field['label'] . ' is not a valid audio URL.';
                            }
                            break;

                        case Form::RULE_URL_IMAGE:
                            if (!preg_match(Form::REGEX_URL_IMAGE, $fieldVal)) {
                                $this->errors[] = $field['label'] . ' is not a valid image URL.';
                            }
                            break;

                        case Form::RULE_URL_VIDEO:
                            if (!preg_match(Form::REGEX_URL_VIDEO, $fieldVal)) {
                                $this->errors[] = $field['label'] . ' is not a valid video URL.';
                            }
                            break;

                        case Form::RULE_MAXLEN:
                            if (strlen($fieldVal) > $operator) {
                                $this->errors[] = $field['label'] . ' is too long.';
                            }
                            break;

                        case Form::RULE_MINLEN:
                            if (strlen($fieldVal) < $operator) {
                                $this->errors[] = $field['label'] . ' is too short.';
                            }
                            break;

                        case Form::RULE_IMAGE_UPLOAD:
                            // check for the upload folder
                            if (!is_dir(UPLOADDIR) || !is_writable(UPLOADDIR)) {
                                // ah, hrm, I see. Let's try to make it -- save them some time
                                $madeDir = @mkdir(UPLOADDIR, 0666);
                                if ($madeDir !== true) {
                                    $this->errors[] = $field['label'] . ' upload can not be processed until the directory '. UPLOADDIR .' exists and is writeable.';
                                }
                            }
                            // TODO: Using _FILES and _POST here is cheating...
                            $file_real = isset($_FILES['file_' . $field['id']]) && !empty($_FILES['file_' . $field['id']]['tmp_name']) ? $_FILES['file_' . $field['id']] : false;
                            $file_url  = isset($_POST['url_' . $field['id']]) ? $_POST['url_' . $field['id']] : false;
                            if ($file_real) {
                                $imageInfo = getimagesize($file_real['tmp_name']);
                                if ($imageInfo === false || !in_array($imageInfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP))) {
                                    $this->errors[] = $field['label'] . ' upload is not an image file.';
                                }
                            }
                            elseif ($file_url) {
                                if (!preg_match(Form::REGEX_URL_IMAGE, $file_url)) {
                                    // TODO: This might be troublesome for sites like imgur which don't need a file extension...
                                    $this->errors[] = $field['label'] . ' URL is not valid.';
                                }
                                else {
                                    $imageInfo = getimagesize($file_url);
                                    if ($imageInfo === false || !in_array($imageInfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP))) {
                                        $this->errors[] = $field['label'] . ' URL does not point at a valid image.';
                                    }
                                }
                            }
                            break;

                        case Form::RULE_VALID_OPTION:
                            if (!in_array($fieldVal, $field['options'])) {
                                $this->errors[] = 'Invalid selection ' . $field['id'];
                            }
                            break;

                    }
                }
            }
        }

        return count($this->errors) == 0;
        
    }
    
}