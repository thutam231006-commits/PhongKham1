<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bổ sung danh sách tệp đính kèm do chuyên viên đính kèm khi gửi kết quả
     * tư vấn cho khách hàng. Lưu JSON gồm: tên hiển thị, đường dẫn trên disk
     * `local`, kích thước và MIME type — nội dung tệp KHÔNG nằm trong DB.
     */
    public function up(): void
    {
        Schema::table('consultation_records', function (Blueprint $table): void {
            $table->json('attachments')->nullable()->after('customer_recommendation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_records', function (Blueprint $table): void {
            $table->dropColumn('attachments');
        });
    }
};
