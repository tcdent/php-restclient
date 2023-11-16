<?php declare(strict_types=1);

namespace RestClient\Exception;

trait _Trait {}
class Base extends \Exception {
    use _Trait;
}
class Fatal extends Base {
    use _Trait;
}
class InvalidArgument extends \InvalidArgumentException {
    use _Trait;
}
class BadMethodCall extends \BadMethodCallException {
    use _Trait;
}
class OutOfBounds extends \OutOfBoundsException {
    use _Trait;
}
class Timeout extends Base {
    use _Trait;
}
