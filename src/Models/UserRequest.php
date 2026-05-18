<?php

namespace RandomChat\Models;

class UserRequest {
    public $resourceId;
    public $groupSize;
    public $chatterName;
    public $joinTime;

    public function __construct($resourceId, $groupSize, $chatterName) {
        $this->resourceId = $resourceId;
        $this->groupSize = $groupSize;
        $this->chatterName = $chatterName;
        $this->joinTime = time();
    }
}
