<?php

namespace Drupal\openai_ckeditor\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use OpenAI\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Returns responses for CKEditor integration routes.
 */
class Completion implements ContainerInjectionInterface {

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * The Completion controller constructor.
   *
   * @param \OpenAI\Client $client
   *   The openai.client service.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai.client')
    );
  }

  /**
   * Builds the response.
   */
  public function generate(Request $request) {
    $data = json_decode($request->getContent());
    $use_chat_endpoint = str_contains($data->options->model, 'gpt');

    if (!$use_chat_endpoint) {
      $stream = $this->client->completions()->createStreamed(
        [
          'model' => $data->options->model ?? 'text-davinci-003',
          'prompt' => trim($data->prompt),
          'temperature' => floatval($data->options->temperature),
          'max_tokens' => (int) $data->options->max_tokens,
        ]
      );
    } else {
      $messages = [
        ['role' => 'system', 'content' => 'You are an expert in content editing and an assistant to a user writing content for their website. Please return all answers without using first, second, or third person voice.'],
        ['role' => 'user', 'content' => trim($data->prompt)]
      ];

      $stream = $this->client->chat()->createStreamed(
        [
          'model' => $data->options->model ?? 'gpt-3.5-turbo',
          'messages' => $messages,
          'temperature' => floatval($data->options->temperature),
          'max_tokens' => (int) $data->options->max_tokens,
        ]
      );
    }

    return new StreamedResponse(function () use ($stream, $use_chat_endpoint) {
      foreach ($stream as $data) {
        echo (!$use_chat_endpoint) ? $data->choices[0]->text : $data->choices[0]->delta->content;
        ob_flush();
        flush();
      }
    }, 200, ['Content-Type' => 'text/plain']);
  }

}
