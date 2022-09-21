<?php

namespace Drupal\gepsis\Controller;

use http\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;

class AjaxController extends ControllerBase {

    // https://www.drupal.org/forum/support/module-development-and-code-questions/2019-03-12/how-to-pass-variable-to-controllerphp
    // https://www.drupal.org/forum/support/module-development-and-code-questions/2016-12-06/load-a-ajax-form-with-ajax-call

    /**
     * The form builder.
     *
     * @var \Drupal\Core\Form\FormBuilder
     */
    protected $formBuilder;

    /**
     * The ModalFormExampleController constructor.
     *
     * @param \Drupal\Core\Form\FormBuilder $formBuilder
     *   The form builder.
     */
    public function __construct(FormBuilder $formBuilder) {
        $this->formBuilder = $formBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The Drupal service container.
     *
     * @return static
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('form_builder')
        );
    }

    /**
     * Callback for opening the modal form.
     */
    public function openModalForm() {
        $url = $_POST['url'];
        return;

        return new TrustedRedirectResponse('http://www.bbc.com/');
        return $this->redirect('user.page');
        return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));

        $response = new AjaxResponse();
        //https://gorannikolovski.com/blog/display-modal-page-load-drupal
        $modal_form = $this->formBuilder()->getForm('nouveau_salarie');
        $options = [
            'width' => '75%',
        ];
        $response->addCommand(new OpenModalDialogCommand('My Modal', $modal_form, $options));
        return $response;
    }

}