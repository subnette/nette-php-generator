<?php declare(strict_types=1);

use Nette\PhpGenerator\Factory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


#[Attribute]
class FixtureAttribute
{
}


class EmptyClass
{
}


class PromotedOnly
{
	public function __construct(public int $promoted = 3)
	{
	}
}


enum FixtureEnum: string
{
	case First = 'first';
}


class UnresolvedParent
{
	public $bad = FACTORY_MISSING_OTHER;
}


class InheritedOnly extends UnresolvedParent
{
}


trait UnresolvedTrait
{
	public $bad = FACTORY_MISSING_TRAIT;
}


class TraitOnly
{
	use UnresolvedTrait;
}


class Unresolved
{
	#[FixtureAttribute(FACTORY_MISSING_ATTRIBUTE)]
	public int $valid = 1;
	public $bad = FACTORY_MISSING_OTHER;
}


class InheritedUnresolved extends UnresolvedParent
{
	public int $valid = 1;
}


class MethodError extends UnresolvedParent
{
	#[FixtureAttribute(FACTORY_MISSING_METHOD)]
	public function method(): void
	{
	}
}


$factory = new Factory;
foreach ([EmptyClass::class, PromotedOnly::class, InheritedOnly::class, TraitOnly::class, FixtureEnum::class] as $name) {
	$class = $factory->fromClassReflection(new ReflectionClass($name));
	if ($name !== FixtureEnum::class) {
		Assert::same([], $class->getProperties());
	}
}

Assert::exception(
	fn() => $factory->fromClassReflection(new ReflectionClass(Unresolved::class)),
	Error::class,
	'Undefined constant "FACTORY_MISSING_OTHER"',
);
Assert::exception(
	fn() => $factory->fromPropertyReflection(new ReflectionProperty(Unresolved::class, 'valid')),
	Error::class,
	'Undefined constant "FACTORY_MISSING_OTHER"',
);
Assert::exception(
	fn() => $factory->fromClassReflection(new ReflectionClass(InheritedUnresolved::class)),
	Error::class,
	'Undefined constant "FACTORY_MISSING_OTHER"',
);
Assert::exception(
	fn() => $factory->fromClassReflection(new ReflectionClass(MethodError::class)),
	Error::class,
	'Undefined constant "FACTORY_MISSING_METHOD"',
);
