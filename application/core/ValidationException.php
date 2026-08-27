<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thrown for intentional business-rule and input-validation rejections.
 * These are expected outcomes — NOT system errors.
 * Catch blocks must NOT call notifyError() for this exception type.
 */
class ValidationException extends RuntimeException {}
