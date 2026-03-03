import { router } from "@inertiajs/react";



export default function HeaderWholesalers({ permissions, isOpenTab, setIsOpenTab }) {

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
      {permissions?.wholesalers_access && (
        <button id="Wholesaler-tab" className={` ${isOpenTab === 'Wholesaler' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Wholesaler', 'admin.wholesalers.index')} >Wholesalers</button>
      )}
      {permissions?.wholesaler_contacts_access && (
        <button id="wholesaler_contacts-tab" className={` ${isOpenTab === 'wholesaler_contacts' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('wholesaler_contacts', 'admin.wholesaler-contacts.index')} >Wholesaler Contact</button>
      )}
      {permissions?.wholesaler_rates_access && (
        <button id="wholesaler_rates-tab" className={` ${isOpenTab === 'wholesaler_rates' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('wholesaler_rates', 'admin.wholesaler-rates.index')} >Wholesaler Rates</button>
      )}
    </div>
  );
}