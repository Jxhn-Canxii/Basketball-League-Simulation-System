export const eliminationFormatter = (type) => {
    switch (type) {
        case 1:
            return 'Single Elimination'
            break;
        case 2:
            return 'Single Round Robin'
            break;
        case 3:
            return 'Double Round Robin'
            break;
        default:
            return 'Invalid'
            break;
    }
    // <option value="0">Select Type</option>
    // <option value="1">Single Elimination</option>
    // <option value="2">Single Round Robin</option>
    // <option value="3">Double Round Robin</option>
};
export const roundNameFormatter = (round) => {
    if (typeof round === 'number') {
        return `Round # ${round}`;
    }

    switch (round) {
        case 'play_ins_elims_round_1':
            return 'Conference Play-ins (7th vs 8th)'
        case 'play_ins_elims_round_2':
            return 'Conference Play-ins (9th vs 10th)'
        case 'play_ins_elims':
            return 'Conference Play-ins'
        case 'play_ins_finals':
            return 'Conference Play-ins Finals'
        case 'round_of_32':
            return 'Conference Round of 16'
            break;
        case 'round_of_16':
            return 'Conference Quarterfinals'
            break;
        case 'quarter_finals':
            return 'Conference Semi-Finals'
            break;
        case 'semi_finals':
            return 'Conference Finals'
            break;
        case 'interconference_semi_finals':
            return 'The Big 4'
            break;
        case 'finals':
            return 'The Finals'
            break;
        default:
            return round;
            break;
    }
}
export const roundGridFormatter = (round,start) => {
    const playIns = true;
    if(start == 32){
        switch (round) {
            case 'play_ins_elims':
                return 3;
                 break;
            case 'play_ins_finals':
                return 4;
                    break;
            case 'round_of_32':
                return 5;
                 break;
            case 'round_of_16':
               return 6;
                break;
            case 'quarter_finals':
                return 7;
                break;
            case 'semi_finals':
                return 8;
                break;
            case 'interconference_semi_finals':
                return 9;
                break;
            case 'finals':
                return 10;
                break;
            default:
                return 2;
                break;
        }
    }else{
        switch (round) {
            case 'play_ins_elims_round_1':
                return 4;
                 break;
            case 'play_ins_elims_round_2':
                return 5;
                    break;
            case 'play_ins_finals':
                return 6;
                    break;
            case 'round_of_16':
                return 7;
                 break;
            case 'quarter_finals':
                return 8;
                break;
            case 'semi_finals':
                return 9;
            case 'interconference_semi_finals':
                return 10;
                break;
            case 'finals':
                return 11;
                break;
            default:
                return 2;
                break;
        }
    }

}
export const roundStatusFormatter = (round,start,playIns) => {
    let newRound;
    if(start == 32){
        switch (round) {
            case 'start':
                newRound = 'round_of_32';
                break;
            case 'round_of_32':
                newRound = 'round_of_16';
                break;
            case 'round_of_16':
                newRound = 'quarter_finals';
                break;
            case 'quarter_finals':
                newRound = 'semi_finals';
                break;
            case 'semi_finals':
                newRound = 'interconference_semi_finals';
                break;
            case 'interconference_semi_finals':
                newRound = 'finals';
                break;
            default:
                newRound = 'invalid';
                break;
        }
    }
    else if(start == 16 && playIns == false){
        switch (round) {
            case 'start':
                newRound = 'round_of_16';
                break;
            case 'round_of_16':
                newRound = 'quarter_finals';
                break; 
            case 'quarter_finals':
                newRound = 'semi_finals';
                break;
            case 'semi_finals':
                newRound = 'finals';
                break;
            case 'finals':
                newRound = 'finals';
                break;
            default:
                newRound = 'invalid';
                break;
        }
    }
    else if(start == 16 && playIns == true){
        switch (round) {
            case 'start':
                newRound = 'play_ins_elims_round_1';
                break;
            case 'play_ins_elims_round_1':
                newRound = 'play_ins_elims_round_2';
                break;
            case 'play_ins_elims_round_2':
                newRound = 'play_ins_finals';
                break;
            case 'play_ins_finals':
                newRound = 'round_of_16';
                break;
            case 'round_of_16':
                newRound = 'quarter_finals';
                break;
            case 'quarter_finals':
                newRound = 'semi_finals';
                break;
            case 'semi_finals':
                newRound = 'interconference_semi_finals';
                break;
            case 'interconference_semi_finals':
                newRound = 'finals';
                break;
            default:
                newRound = 'invalid';
                break;
        }
    }
    else if(start == 8){
        switch (round) {
            case 'start':
                newRound = 'quarter_finals';
                break;
            case 'quarter_finals':
                newRound = 'semi_finals';
                break;
            case 'semi_finals':
                newRound = 'interconference_semi_finals';
                break;
            case 'interconference_semi_finals':
                newRound = 'finals';
                break;
            default:
                newRound = 'invalid';
                break;
        }
    }

    return newRound;
}

export const generateRandomKey = () => {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const length = 10; // You can adjust the length of the key as needed
    let result = '';
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
};
export const moneyFormatter = (amount) => {
    // Check if amount is not a valid number
    if (isNaN(amount) || amount === null || amount === undefined) {
        return ''; // Return empty string
    }
    // Convert amount to number and format with commas for thousands separator
    return Number(amount).toLocaleString('en-US', {maximumFractionDigits: 2});
}
export const playerStatusClass = (isActive) => {
    return isActive ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800";
};

export const playerStatusText = (isActive) => {
    return isActive ? "Active" : "Waived";
};
export const playerExpStatusClass = (experience) => {
    if (parseFloat(experience) == 0) {
        return "bg-green-100 text-green-800"; // Rookie
    } else if (parseFloat(experience) == 2) {
        return "bg-red-100 text-red-800"; // Sophomore
    }
    else if (parseFloat(experience) > 2) {
        return "bg-yellow-100 text-yellow-800"; // Sophomore
    }
    else {
        return "text-gray-800"; // Veteran
    }
};

export const playerExpStatusText = (experience) => {
    if (parseFloat(experience) == 1) {
        return "Rookie";
    } else if (parseFloat(experience) == 2) {
        return "Sophomore";
    } else if (parseFloat(experience) > 2) {
        return "Veteran";
    }
    else {
        return "None";
    }
};

// Remove roleClasses and update roleBadgeClass
export const roleBadgeClass = (role) => {
  const baseClasses = 'rounded-full px-2 py-0.5 text-xs font-medium capitalize shadow-sm';
  
  const roleStyles = {
    'star player': {
      background: 'bg-yellow-500',
      text: 'text-white',
      border: 'border border-yellow-400',
      hover: 'hover:bg-yellow-600'
    },
    'all star': {
      background: 'bg-red-500',
      text: 'text-white',
      border: 'border border-red-400',
      hover: 'hover:bg-red-600'
    },
    'starter': {
      background: 'bg-blue-500',
      text: 'text-white',
      border: 'border border-blue-400',
      hover: 'hover:bg-blue-600'
    },
    'role player': {
      background: 'bg-green-500',
      text: 'text-white',
      border: 'border border-green-400',
      hover: 'hover:bg-green-600'
    },
    'bench': {
      background: 'bg-gray-400',
      text: 'text-white',
      border: 'border border-gray-300',
      hover: 'hover:bg-gray-500'
    }
  };

  const style = roleStyles[role.toLowerCase()] || {
    background: 'bg-gray-300',
    text: 'text-gray-700',
    border: 'border border-gray-200',
    hover: 'hover:bg-gray-400'
  };

  return `${baseClasses} ${style.background} ${style.text} ${style.hover} transition-colors duration-150`;
};

// Add new badge classes for different states
export const statusBadgeClass = (status, size = 'sm') => {
  const sizes = {
    'xs': 'px-1.5 py-0.5 text-xs',
    'sm': 'px-2 py-1 text-sm',
    'md': 'px-3 py-1.5 text-base'
  };

  const baseClasses = `inline-flex items-center rounded-full font-medium ${sizes[size]}`;
  
  const statusStyles = {
    'active': 'bg-green-100 text-green-800 border border-green-200',
    'inactive': 'bg-red-100 text-red-800 border border-red-200',
    'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    'injured': 'bg-orange-100 text-orange-800 border border-orange-200',
    'suspended': 'bg-purple-100 text-purple-800 border border-purple-200'
  };

  return `${baseClasses} ${statusStyles[status] || 'bg-gray-100 text-gray-800 border border-gray-200'}`;
};

export const playerFormatter = (name) => {
    const nameParts = name.split(" "); // Split the name into parts

    // Assume the last part is the surname
    const firstName = nameParts[0];
    const surName = nameParts[nameParts.length - 1];

    // Define a maximum length for the surname
    const maxnameLength = 7; // You can adjust this value as needed

    // Check if the name is too long
    if (name.length > maxnameLength) {
        return `${firstName[0]}. ${surName}`; // Format as "F. Surname"
    } else {
        return name; // Return the name as is if the surname is not too long
    }
};

export const getTransactionIcon = (status) => {
    switch (status) {
      case 'star player change':
        return 'fas fa-star text-yellow-500';
      case 'waived':
        return 'fas fa-user-minus text-red-500';
      case 'released':
        return 'fas fa-door-open text-gray-500';
      case 'signed':
        return 'fas fa-file-signature text-green-500';
      case 'role change':
        return 'fas fa-exchange-alt text-blue-500';
      default:
        return 'fas fa-circle-info text-gray-500';
    }
};

export const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'star player change':
        return 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs';
      case 'waived':
        return 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs';
      case 'released':
        return 'bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs';
      case 'signed':
        return 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs';
      case 'role change':
        return 'bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs';
      default:
        return 'bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs';
    }
};

export const formatStatus = (status) => {
    switch (status) {
      case 'star player change':
        return 'Star Player Change';
      case 'waived':
        return 'Waived';
      case 'released':
        return 'Released';
      case 'signed':
        return 'Signed';
      case 'transferred':
        return 'Transferred';
      default:
        return status.charAt(0).toUpperCase() + status.slice(1);
    }
};