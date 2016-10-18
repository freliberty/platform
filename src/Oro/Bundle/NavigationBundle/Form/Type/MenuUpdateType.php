<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\LocaleBundle\Form\Type\TranslatedLocalizedFallbackValueCollectionType;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

class MenuUpdateType extends AbstractType
{
    const NAME = 'oro_navigation_menu_update';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'titles',
                TranslatedLocalizedFallbackValueCollectionType::class,
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.title.label',
                    'options' => ['constraints' => [new NotBlank()]]
                ]
            )
            ->add(
                'descriptions',
                TranslatedLocalizedFallbackValueCollectionType::class,
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.description.label',
                    'type' => 'textarea',
                    'field' => 'text',
                    'options' => ['constraints' => [new NotBlank()]]
                ]
            )
            ->add(
                'uri',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.uri.label',
                ]
            )
            ->add(
                'active',
                'checkbox',
                [
                    'label' => 'oro.navigation.menuupdate.active.label',
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MenuUpdate::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
