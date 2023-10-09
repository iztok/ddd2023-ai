<?php

declare(strict_types=1);

namespace Drupal\openai_ckeditor\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 OpenAI Completion plugin configuration.
 */
class OpenAI extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The default configuration for this plugin.
   *
   * @var string[][]
   */
  const DEFAULT_CONFIGURATION = [
    'completion' => [
      'enabled' => FALSE,
      'model' => 'gpt-3.5-turbo',
      'temperature' => 0.2,
      'max_tokens' => 512,
    ]
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return static::DEFAULT_CONFIGURATION;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['completion'] = [
      '#title' => $this->t('Text completion'),
      '#type' => 'details',
      '#description' => $this->t('The following setting controls the behavior of the text completion, translate, tone, and summary actions in CKEditor.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['completion']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->configuration['completion']['enabled'] ?? FALSE,
      '#description' => $this->t('Enable this editor feature.'),
    ];

    $form['completion']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default model'),
      '#options' => [
        'gpt-4' => 'gpt-4',
        'gpt-4-0314' => 'gpt-4-0314',
        'gpt-4-32k' => 'gpt-4-32k',
        'gpt-4-32k-0314' => 'gpt-4-32k-0314',
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
        'gpt-3.5-turbo-0301' => 'gpt-3.5-turbo-0301',
        'text-davinci-003' => 'text-davinci-003',
        'text-curie-001' => 'text-curie-001',
        'text-babbage-001' => 'text-babbage-001',
        'text-ada-001' => 'text-ada-001',
      ],
      '#default_value' => $this->configuration['completion']['model'] ?? 'gpt-3.5-turbo',
      '#description' => $this->t('Select which model to use to analyze text. See the <a href="@link">model overview</a> for details about each model. Note that newer GPT models may be invite only.', ['@link' => 'https://platform.openai.com/docs/models']),
    ];

    $form['completion']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .1,
      '#default_value' => $this->configuration['completion']['temperature'] ?? '0.2',
      '#description' => $this->t('What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.'),
    ];

    $form['completion']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max tokens'),
      '#min' => 128,
      '#max' => 32768,
      '#step' => 1,
      '#default_value' => $this->configuration['completion']['max_tokens'] ?? '128',
      '#description' => $this->t('The maximum number of tokens to generate in the completion. The token count of your prompt plus max_tokens cannot exceed the model\'s context length. Most models have a context length of 2048-4097 tokens. Newer GPT-4 models support upwards of 32768 tokens. Check the <a href="@link">models overview</a> for more details.', ['@link' => 'https://platform.openai.com/docs/models/gpt-4']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $model = $values['completion']['model'];
    $max_tokens = (int) $values['completion']['max_tokens'];

    switch ($model) {
      case 'gpt-4':
      case 'gpt-4-0314':
        if ($max_tokens > 8192) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 8192 tokens. Please reduce the max token value to 8192 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo':
      case 'gpt-3.5-turbo-0301':
        if ($max_tokens > 4096) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 4096 tokens. Please reduce the max token value to 4096 or lower.'));
        }
        break;
      case 'gpt-3.5-turbo-16k':
        if ($max_tokens > 16384) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 16384 tokens. Please reduce the max token value to 16384 or lower.'));
        }
        break;
      case 'text-davinci-003':
        if ($max_tokens > 4097) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 4097 tokens. Please reduce the max token value to 4097 or lower.'));
        }
        break;
      case 'text-curie-001':
      case 'text-babage-001':
      case 'text-ada-001':
        if ($max_tokens > 2049) {
          $form_state->setError($form['completion']['max_tokens'], $this->t('The model you have selected only supports a maximum of 2049 tokens. Please reduce the max token value to 2049 or lower.'));
        }
        break;
      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configuration['completion']['enabled'] = (bool) $values['completion']['enabled'];
    $this->configuration['completion']['model'] = $values['completion']['model'];
    $this->configuration['completion']['temperature'] = floatval($values['completion']['temperature']);
    $this->configuration['completion']['max_tokens'] = (int) $values['completion']['max_tokens'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $options = $static_plugin_config;
    $config = $this->getConfiguration();

    return [
      'openai_ckeditor_openai' => [
        'completion' => [
          'enabled' => $config['completion']['enabled'] ?? $options['completion']['enabled'],
          'model' => $config['completion']['model'] ?? $options['completion']['model'],
          'temperature' => $config['completion']['temperature'] ?? $options['completion']['temperature'],
          'max_tokens' => $config['completion']['max_tokens'] ?? $options['completion']['max_tokens'],
        ]
      ]
    ];
  }

}
