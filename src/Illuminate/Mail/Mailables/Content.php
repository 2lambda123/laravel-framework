<?php

namespace Illuminate\Mail\Mailables;

class Content
{
    /**
     * The Blade view that should be rendered for the mailable.
     *
     * @var string|null
     */
    public $view;

    /**
     * The Blade view that represents the text version of the message.
     *
     * @var string|null
     */
    public $text;

    /**
     * The Blade view that represents the Markdown version of the message.
     *
     * @var string|null
     */
    public $markdown;

    /**
     * The message's view data.
     *
     * @var array
     */
    public $with;

    /**
     * Create a new content definition.
     *
     * @param  string|null  $view
     * @param  string|null  $text
     * @param  string|null  $markdown
     * @param  array  $with
     */
    public function __construct(string $view = null, string $text = null, $markdown = null, array $with = [])
    {
        $this->view = $view;
        $this->text = $text;
        $this->markdown = $markdown;
        $this->with = $with;
    }
}
