<?php

namespace App\UpdateHandlers;

interface UpdateHandler {

    public function getAction($update);

  }