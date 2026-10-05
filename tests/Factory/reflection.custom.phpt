<?php declare(strict_types=1);

use Nette\PhpGenerator\Factory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class Fixture
{
	public int $first = 1;
	public int $second = 2;
}


class CustomDefaults extends ReflectionClass
{
	public int $calls = 0;


	public function getDefaultProperties(): array
	{
		$this->calls++;
		return ['first' => 100 + $this->calls, 'second' => 200 + $this->calls];
	}
}


class CustomProperty extends ReflectionProperty
{
	public function __construct(string $name, private ReflectionClass $declaring)
	{
		parent::__construct(Fixture::class, $name);
	}


	public function getDeclaringClass(): ReflectionClass
	{
		return $this->declaring;
	}
}


class CustomReflection extends ReflectionClass
{
	public function __construct(private ReflectionClass $declaring)
	{
		parent::__construct(Fixture::class);
	}


	public function getProperties(?int $filter = null): array
	{
		return [new CustomProperty('first', $this->declaring), new CustomProperty('second', $this->declaring)];
	}


	public function getDefaultProperties(): array
	{
		throw new RuntimeException('Must use each property declaring class.');
	}
}


class CustomObjectReflection extends ReflectionObject
{
	public function __construct(private ReflectionClass $declaring)
	{
		parent::__construct(new Fixture);
	}


	public function getProperties(?int $filter = null): array
	{
		return [new CustomProperty('first', $this->declaring), new CustomProperty('second', $this->declaring)];
	}


	public function getDefaultProperties(): array
	{
		throw new RuntimeException('Must use each property declaring class.');
	}
}


$factory = new Factory;
foreach ([CustomReflection::class, CustomObjectReflection::class] as $reflectionClass) {
	$defaults = new CustomDefaults(Fixture::class);
	$class = $factory->fromClassReflection(new $reflectionClass($defaults));
	Assert::same(['first', 'second'], array_keys($class->getProperties()));
	Assert::same(2, $defaults->calls);
	Assert::same(101, $class->getProperty('first')->getValue());
	Assert::same(202, $class->getProperty('second')->getValue());
	Assert::same(103, $factory->fromPropertyReflection(new CustomProperty('first', $defaults))->getValue());
	Assert::same(3, $defaults->calls);
}
Assert::same(1, $factory->fromClassReflection(new ReflectionClass(Fixture::class))->getProperty('first')->getValue());
