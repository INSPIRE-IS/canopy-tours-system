import { router } from "@inertiajs/react";



export default function ServicesHeader({ isOpenTab, setIsOpenTab }) {

    function toggleDropdown (tab, routeName) {
        setIsOpenTab(prev => (prev === tab ? '' : tab));
        router.get(route(routeName), {}, {
        preserveScroll: true,
        preserveState: true,
        });
    }

    //const [isOpenTab, setIsOpenTab] = useState('Services');


    return (
          <div className="flex pt-2 space-x-6 border-b border-gray-200">
            <button id="Services-tab" className={` ${isOpenTab === 'Services' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-active pb-2 cursor-pointer`} onClick={()=> toggleDropdown('Services', 'admin.services.index')} >Services</button>
            <button id="Seasons-tab" className={` ${isOpenTab === 'Seasons' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer`} onClick={()=> toggleDropdown('Seasons', 'admin.seasons.index')} >Seasons</button>
            <button id="Inventory-tab" className={` ${isOpenTab === 'Inventory' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('Inventory', 'admin.inventories.index')} >Inventory</button>
            <button id="Closeout-tab" className={` ${isOpenTab === 'Closeout' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('Closeout', 'admin.closeouts.index')} >Closeouts</button>
            <button id="Wholesaler-tab" className={` ${isOpenTab === 'Wholesaler' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('Wholesaler', 'admin.wholesalers.index')} >Wholesalers</button>
            <button id="Voucher-tab" className={` ${isOpenTab === 'Voucher' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('Voucher', 'admin.vouchers.index')} >Vouchers</button>
            <button id="wholesaler_rates-tab" className={` ${isOpenTab === 'wholesaler_rates' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('wholesaler_rates', 'admin.wholesaler_rates.index')} >wholesaler_rates</button>
            <button id="discounts-tab" className={` ${isOpenTab === 'discounts' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('discounts', 'admin.discounts.index')} >discounts</button>
            <button id="promotions-tab" className={` ${isOpenTab === 'promotions' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('promotions', 'admin.promotions.index')} >promotions</button>
          </div>
    );
}