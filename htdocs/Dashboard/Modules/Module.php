<?php

abstract class Module {
	abstract function populate($handle, &$values, &$sections);
	abstract function getName();
}