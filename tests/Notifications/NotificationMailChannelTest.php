<?php

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationMailChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testMailIsSentByChannel()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::type('Closure'));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithSubject()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once()->with('test subject');

            $mock->shouldReceive('to')->once()->with('taylor@laravel.com');

            $mock->shouldReceive('from')->never();

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithoutSubjectAutogeneratesSubjectFromClassName()
    {
        $notification = new NotificationMailChannelTestNotificationNoSubject;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once()->with('Notification Mail Channel Test Notification No Subject');

            $mock->shouldReceive('to')->once()->with('taylor@laravel.com');

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithMultipleSenders()
    {
        $notification = new NotificationMailChannelTestNotification;
        $notifiable = new NotificationMailChannelTestNotifiableMultipleEmails;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->never();

            $mock->shouldReceive('bcc')->with(['taylor@laravel.com', 'jeffrey@laracasts.com']);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithFromAddress()
    {
        $notification = new NotificationMailChannelTestNotificationWithFromAddress;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('from')->with('test@mail.com', 'Test Man');

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithFromAddressAndNoName()
    {
        $notification = new NotificationMailChannelTestNotificationWithFromAddressNoName;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once();

            $mock->shouldReceive('from')->with('test@mail.com', null);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithToAddress()
    {
        $notification = new NotificationMailChannelTestNotificationWithToAddress;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once()->with('jeffrey@laracasts.com');

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithToCcEmails()
    {
        $notification = new NotificationMailChannelTestNotificationWithCcEmails;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once()->with('taylor@laravel.com');

            $mock->shouldReceive('cc')->once()->with(['cc1@email.com', 'cc2@email.com']);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithPriority()
    {
        $notification = new NotificationMailChannelTestNotificationWithPriority;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $message = $notification->toMail($notifiable);
        $data = $message->toArray();

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class)
        );

        $views = ['notifications::email', 'notifications::email-plain'];

        $mailer->shouldReceive('send')->with($views, $data, Mockery::on(function ($closure) {
            $mock = Mockery::mock('Illuminate\Mailer\Message');

            $mock->shouldReceive('subject')->once();

            $mock->shouldReceive('to')->once()->with('taylor@laravel.com');

            $mock->shouldReceive('setPriority')->once()->with(1);

            $closure($mock);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithMailableContract()
    {
        $notification = new NotificationMailChannelTestNotificationWithMailableContract;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Mail\Mailer::class)
        );

        $mailer->shouldReceive('send')->twice()->with(Mockery::on(function ($mailable) use ($mailer) {
            if ($mailable instanceof \Illuminate\Contracts\Mail\Mailable) {
                $mailable->send($mailer);

                return true;
            }

            if ($mailable == 'test:view') {
                return true;
            }

            return false;
        }));

        $channel->send($notifiable, $notification);
    }

    public function testMessageWithMailableShouldQueueContract()
    {
        $notification = new NotificationMailChannelTestNotificationWithMailableShouldQueueContract;
        $notifiable = new NotificationMailChannelTestNotifiable;

        $channel = new Illuminate\Notifications\Channels\MailChannel(
            $mailer = Mockery::mock(Illuminate\Mail\Mailer::class)
        );

        $queue = Mockery::mock(Illuminate\Contracts\Queue\Factory::class);

        $queue->shouldReceive('connection')->once()->with(Mockery::on(function ($connection) {
            return $connection == 'test';
        }));

        $mailer->shouldReceive('queue')->once()->with(Mockery::on(function ($mailable) use ($queue) {
            if (! $mailable instanceof \Illuminate\Contracts\Mail\Mailable) {
                return false;
            }

            $mailable->queue($queue);

            return true;
        }));

        $channel->send($notifiable, $notification);
    }
}

class NotificationMailChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;

    public $email = 'taylor@laravel.com';
}

class NotificationMailChannelTestNotifiableMultipleEmails
{
    use Illuminate\Notifications\Notifiable;

    public function routeNotificationForMail()
    {
        return ['taylor@laravel.com', 'jeffrey@laracasts.com'];
    }
}

class NotificationMailChannelTestNotification extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('test subject');
    }
}

class NotificationMailChannelTestNotificationNoSubject extends Notification
{
    public function toMail($notifiable)
    {
        return new MailMessage;
    }
}

class NotificationMailChannelTestNotificationWithFromAddress extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from('test@mail.com', 'Test Man');
    }
}

class NotificationMailChannelTestNotificationWithFromAddressNoName extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from('test@mail.com');
    }
}

class NotificationMailChannelTestNotificationWithToAddress extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->to('jeffrey@laracasts.com');
    }
}

class NotificationMailChannelTestNotificationWithCcEmails extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->cc(['cc1@email.com', 'cc2@email.com']);
    }
}

class NotificationMailChannelTestNotificationWithPriority extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->priority(1);
    }
}

class NotificationMailChannelTestMail implements \Illuminate\Contracts\Mail\Mailable  {

    public function send(\Illuminate\Contracts\Mail\Mailer $mailer)
    {
        $mailer->send('test:view');
    }

    public function queue(\Illuminate\Contracts\Queue\Factory $queue){}

    public function later($delay, \Illuminate\Contracts\Queue\Factory $queue) {}
}

class NotificationMailChannelTestNotificationWithMailableContract extends Notification
{
    public function toMail($notifiable)
    {
        return new NotificationMailChannelTestMail();
    }
}

class NotificationMailChannelTestShouldQueueMail implements \Illuminate\Contracts\Mail\Mailable , \Illuminate\Contracts\Queue\ShouldQueue  {

    public function send(Illuminate\Contracts\Mail\Mailer $mailer) {}

    public function queue(Illuminate\Contracts\Queue\Factory $queue)
    {
        $queue->connection('test');
    }

    public function later($delay, Illuminate\Contracts\Queue\Factory $queue) {}
}

class NotificationMailChannelTestNotificationWithMailableShouldQueueContract extends Notification
{
    public function toMail($notifiable)
    {
        return new NotificationMailChannelTestShouldQueueMail();
    }
}
