import { router } from "@inertiajs/react";



export default function HeaderMarketing({ permissions, isOpenTab, setIsOpenTab }) {

  function toggleDropdown(tab, routeName) {
    setIsOpenTab(prev => (prev === tab ? '' : tab));
    router.get(route(routeName), {}, {
      preserveScroll: true,
      preserveState: true,
    });
  }

  //const [isOpenTab, setIsOpenTab] = useState('Services');


  return (
    <div className="flex pt-2 space-x-6 border-b border-gray-200">
      {permissions?.vouchers_access && (
        <button id="Voucher-tab" className={` ${isOpenTab === 'Voucher' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Voucher', 'admin.vouchers.index')} >Vouchers</button>
      )}
      {permissions?.discounts_access && (
        <button id="discounts-tab" className={` ${isOpenTab === 'discounts' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('discounts', 'admin.discounts.index')} >Discounts</button>
      )}
      {permissions?.promotions_access && (
        <button id="promotions-tab" className={` ${isOpenTab === 'promotions' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('promotions', 'admin.promotions.index')} >Promotions</button>
      )}
    </div>
  );
}