<?php namespace App\Form\Type;

use App\Entity\EntityRepository;
use App\Entity\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class RandomTextFilter extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('type', EntityType::class, [
				'class' => TextType::class,
				'query_builder' => function (EntityRepository $repo) {
					return $repo->createQueryBuilder('e')
						->orderBy('e.name');
				},
				'multiple' => true,
				'expanded' => true,
			])
			->add('submit', SubmitType::class);
	}

}
