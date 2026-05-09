namespace App\Models;

use App\Models\Concerns\HasLogicalDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPaymentType extends Model
{
    use HasFactory, HasLogicalDelete;

    protected $table = 'contract_payment_types';

    protected $fillable = [
        'nombre',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}