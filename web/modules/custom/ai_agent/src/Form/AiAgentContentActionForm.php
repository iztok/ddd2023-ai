<?php

namespace Drupal\ai_agent\Form;

use Drupal;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for the AI Agent content action.
 */
class AiAgentContentActionForm extends FormBase
{

  /**
   * Generate the JSON data for the content structure.
   */
  public function generateContentStructureJson()
  {
    // Load the entity type manager service.
    $entity_type_manager = \Drupal::service('entity_type.manager');

    // Load the entity field manager service.
    $entity_field_manager = \Drupal::service('entity_field.manager');

    // Get a list of all content types.
    $content_types = $entity_type_manager->getStorage('node_type')->loadMultiple();

    // Loop through each content type and load its field definitions.
    $content_structure = [];
    foreach ($content_types as $content_type) {
      $content_structure[$content_type->id()]['type'] = $content_type->id();
      $content_structure[$content_type->id()]['label'] = $content_type->label();
      // Get the description of the content type.
      $description = $content_type->getDescription();
      // Remove any HTML tags from the description.
      $description = strip_tags($description);
      $content_structure[$content_type->id()]['description'] = $description;
      $fields = $entity_field_manager->getFieldDefinitions('node', $content_type->id());
      foreach ($fields as $field) {
        $content_structure[$content_type->id()]['fields'][$field->getName()] = [
          'machine_name' => $field->getName(),
          'label' => $field->getLabel(),
          'type' => $field->getType(),
        ];
      }
    }

    // Encode the field data as a JSON string.
    $json = json_encode($content_structure);
    return $json;
  }

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
    return 'ai_agent_content_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['message'] = [
      '#type' => 'textarea',
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
    $today = date("Y-m-d\TH:i:s");
    $content_structure = $this->generateContentStructureJson();
    $system = <<<EOT
      You are a helpful AI assistant that generates content for this Drupal 9 website.
      Today's date is $today.
      You can use one of the following types of content:
      $content_structure
      When you get instructions, output only valid JSON with the content that you have created.
      The JSON must include type and title. Return valid field values for Drupal 9.
      Respond only with valid JSON. Omit prose.
      EOT;

    $messages = [
      ['role' => 'system', 'content' => $system],
      ['role' => 'user', 'content' => $message]
    ];
    $response = $this->client->chat()->create(
      [
        'model' => 'gpt-4',
        'messages' => $messages,
        'temperature' => 0.4,
      ],
    );
    $result = $response->toArray();
    $reply = trim($result["choices"][0]["message"]["content"]);
    $node_details = json_decode($reply, true);

    $node = Node::create($node_details);
    $node->setUnpublished();
    $node->save();

    $url = $node->toUrl('edit-form');
    $form_state->setRedirectUrl($url);
  }
}
