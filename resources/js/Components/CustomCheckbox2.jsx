const CustomCheckbox2 = ({ label, checked, onChange }) => {

    return (
        <div className="mb-4">
            <label className="flex items-center space-x-2">
            <input
                type="checkbox"
                checked={checked}
                onChange={onChange} // forward the event
                className="form-checkbox"
            />
            <span>{label}</span>
            </label>
        </div>
    );
};

export default CustomCheckbox2;
