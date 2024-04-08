<?php

namespace dsarhoya\DSYFilesBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileTypeExtension extends AbstractTypeExtension
{
    public function getExtendedType()
    {
        return FileType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'is_image' => false,
            'file_key' => null,
            'file_path' => null,
            'file_url_options' => [],
            'delete_route' => null,
            'delete_route_parameters' => [],
        ]);
    }

    /**
     * @param \dsarhoya\DSYFilesBundle\Form\Extension\FormView      $view
     * @param \dsarhoya\DSYFilesBundle\Form\Extension\FormInterface $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['file_path_and_key'] = null;

        $parentData = $form->getParent()->getData();
        if (null === $parentData) {
            return;
        }

        if ($parentData instanceof \dsarhoya\DSYFilesBundle\Interfaces\IFileEnabledEntity) {
            $view->vars['file_path_and_key'] = empty($parentData->getFileKey())
                ? null
                : sprintf('%s/%s', $parentData->getFilePath(), $parentData->getFileKey());
            $view->vars['is_image'] = $options['is_image'];
            $view->vars['file_url_options'] = $options['file_url_options'];
            $view->vars['delete_route'] = $options['delete_route'];
            $view->vars['delete_route_parameters'] = $options['delete_route_parameters'];
        }

        if (!isset($options['file_key'])) {
            return;
        }
        if (!isset($options['file_path'])) {
            return;
        }

        $view->vars['file_path_and_key'] = sprintf('%s/%s', $options['file_path'], $options['file_key']);
        $view->vars['is_image'] = $options['is_image'];
        $view->vars['file_url_options'] = $options['file_url_options'];
        $view->vars['delete_route'] = $options['delete_route'];
        $view->vars['delete_route_parameters'] = $options['delete_route_parameters'];
    }
}
