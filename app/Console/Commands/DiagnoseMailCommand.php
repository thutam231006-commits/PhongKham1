<?php

namespace App\Console\Commands;

use App\Services\MailHealthService;
use Illuminate\Console\Command;

class DiagnoseMailCommand extends Command
{
    protected $signature = 'mail:diagnose
                            {--to= : Địa chỉ nhận email thử (bỏ trống để chỉ kiểm tra cấu hình)}';

    protected $description = 'Chẩn đoán cấu hình gửi email và kiểm tra kết nối SMTP';

    public function handle(MailHealthService $mailHealth): int
    {
        $this->components->info('Cấu hình mail hiện tại');

        $configuration = $mailHealth->inspectConfiguration();

        foreach ($configuration['details'] as $label => $value) {
            $this->components->twoColumnDetail($label, $value);
        }

        $this->newLine();

        if (! $configuration['ok']) {
            $this->components->error($configuration['message']);

            return self::FAILURE;
        }

        $this->components->info($configuration['message']);

        $recipient = $this->option('to');

        if (blank($recipient)) {
            $this->components->info('Bỏ qua bước gửi thử. Dùng --to=email@example.com để kiểm tra kết nối thật.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Đang gửi email thử tới '.$recipient.'…');

        $result = $mailHealth->verifyConnection($recipient);

        if ($result['ok']) {
            $this->components->info($result['message']);

            return self::SUCCESS;
        }

        $this->components->error($result['message']);

        return self::FAILURE;
    }
}
