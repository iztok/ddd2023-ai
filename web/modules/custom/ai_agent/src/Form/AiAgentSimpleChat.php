<?php

namespace Drupal\ai_agent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AI Agent settings form.
 */
class AiAgentSimpleChat extends FormBase
{

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    $instance = parent::create($container);
    $instance->client = $container->get('openai.client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'ai_agent_simple_chat';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $message = $form_state->getValue('message');

    $system = 'You are a helpful AI assistant.';

    $messages = [
      ['role' => 'system', 'content' => $system],
      ['role' => 'user', 'content' => $message]
    ];

    $response = $this->client->chat()->create(
      [
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'temperature' => 0.4,
      ],
    );
    $result = $response->toArray();

    $reply = trim($result["choices"][0]["message"]["content"]);

    \Drupal::messenger()->addMessage(Markup::create(nl2br($reply)));
  }
}
