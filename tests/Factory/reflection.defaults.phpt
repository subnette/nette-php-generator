<?php declare(strict_types=1);

use Nette\PhpGenerator\Factory;
use Nette\PhpGenerator\PromotedParameter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


#[Attribute(Attribute::TARGET_PROPERTY)]
class PropertyAttribute
{
}


class ParentClass
{
	public int $inherited = 5;
	protected int $overridden = 1;
}


#[AllowDynamicProperties]
class Fixture extends ParentClass
{
	public $implicit;
	public $explicit = null;
	public ?string $nullable = null;
	public int $uninitialized;
	/** Property comment. */
	#[PropertyAttribute('label', number: 3)]
	private array $items = ['a' => 1, 'b' => null];
	protected int $overridden = 2;
	public static int $counter = 7;
	public static int $staticUninitialized;
	public readonly int $readonly;


	public function __construct(public int $promoted = 9)
	{
	}
}


$factory = new Factory;
$reflection = new ReflectionClass(Fixture::class);
$original = $factory->fromClassReflection($reflection);
$expected = [
	'implicit' => [null, null, false, false, false, 'public'],
	'explicit' => [null, null, false, false, false, 'public'],
	'nullable' => [null, 'string', true, false, false, 'public'],
	'uninitialized' => [null, 'int', false, false, false, 'public'],
	'items' => [['a' => 1, 'b' => null], 'array', true, false, false, 'private'],
	'overridden' => [2, 'int', true, false, false, 'protected'],
	'counter' => [7, 'int', true, true, false, 'public'],
	'staticUninitialized' => [null, 'int', false, true, false, 'public'],
	'readonly' => [null, 'int', false, false, true, 'public'],
];

Fixture::$counter = 70;
Fixture::$staticUninitialized = 80;
$instance = new Fixture;
$instance->nullable = 'changed';
$instance->uninitialized = 90;
$instance->dynamic = 'ignored';

foreach ([$reflection, new ReflectionObject($instance)] as $from) {
	$class = $factory->fromClassReflection($from);
	Assert::same(array_keys($expected), array_keys($class->getProperties()));
	foreach ($expected as $name => $state) {
		$property = $class->getProperty($name);
		Assert::same($state, [
			$property->getValue(), $property->getType(), $property->isInitialized(),
			$property->isStatic(), $property->isReadOnly(), $property->getVisibility(),
		]);
		Assert::equal($factory->fromPropertyReflection($reflection->getProperty($name)), $property);
	}
	Assert::same((string) $original, (string) $class);
}

Assert::same('Property comment.', $original->getProperty('items')->getComment());
Assert::same(PropertyAttribute::class, $original->getProperty('items')->getAttributes()[0]->getName());
Assert::same(['label', 'number' => 3], $original->getProperty('items')->getAttributes()[0]->getArguments());
Assert::same(ParentClass::class, $original->getExtends());
Assert::same(5, $factory->fromPropertyReflection($reflection->getProperty('inherited'))->getValue());
Assert::type(PromotedParameter::class, $original->getMethod('__construct')->getParameters()['promoted']);
Assert::same(9, $original->getMethod('__construct')->getParameters()['promoted']->getDefaultValue());
Assert::same(5, $factory->fromClassReflection(new ReflectionClass(ParentClass::class))->getProperty('inherited')->getValue());
Assert::equal($original->getProperties(), $factory->fromClassReflection($reflection)->getProperties());
