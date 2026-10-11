<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    use Sortable, HasTranslations;

    /** Own branches first; dealer/distributor/agent kept for partners added later. */
    public const TYPES = ['head_office', 'factory', 'sales_office', 'dealer', 'distributor', 'agent'];

    /** All 64 districts of Bangladesh (English spelling used by the government portal). */
    public const DISTRICTS = ['Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur', 'Chapainawabganj', 'Chattogram', 'Chuadanga', "Cox's Bazar", 'Cumilla', 'Dhaka', 'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokati', 'Jhenaidah', 'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon'];

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'address', 'contact_person'];

    protected $fillable = ['name', 'type', 'district', 'address', 'contact_person', 'phone', 'email', 'map_url', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
